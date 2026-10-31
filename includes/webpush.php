<?php
// ======================================================
// NOTIFICATIONS WEB PUSH (sans bibliothèque externe)
// - Chiffrement du message : RFC 8291 (aes128gcm)
// - Authentification du serveur : RFC 8292 (VAPID, ES256)
// ======================================================

// Options OpenSSL : sous Windows/WAMP, il faut indiquer openssl.cnf
function openssl_options(array $extra = [])
{
    $cnf = dirname(ini_get('extension_dir')) . '/extras/ssl/openssl.cnf';

    return $extra + (is_file($cnf) && !getenv('OPENSSL_CONF') ? ['config' => $cnf] : []);
}

function b64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode($data)
{
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

// ======================================================
// SECRETS DE L'INSTALLATION (générés une seule fois)
// config/secrets.php : jamais versionné
// ======================================================
function app_secrets()
{
    static $secrets = null;

    if ($secrets !== null) {
        return $secrets;
    }

    $fichier = __DIR__ . '/../config/secrets.php';

    if (is_file($fichier)) {
        return $secrets = require $fichier;
    }

    // Paire de clés VAPID (courbe P-256)
    $opts = openssl_options(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $cle = openssl_pkey_new($opts);
    openssl_pkey_export($cle, $pem, null, $opts);
    $d = openssl_pkey_get_details($cle)['ec'];

    $secrets = [
        'vapid_private_pem' => $pem,
        'vapid_public'      => b64url_encode("\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT)),
        'hmac_secret'       => bin2hex(random_bytes(32)),
    ];

    file_put_contents($fichier, "<?php\n// Généré automatiquement - NE PAS VERSIONNER\nreturn " . var_export($secrets, true) . ";\n", LOCK_EX);

    return $secrets;
}

function vapid_public_key()
{
    return app_secrets()['vapid_public'];
}

// Clé publique brute (65 octets) => ressource OpenSSL
function ec_public_from_raw($raw)
{
    // En-tête DER SubjectPublicKeyInfo pour une clé P-256 non compressée
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";

    return openssl_pkey_get_public($pem);
}

// ======================================================
// CHIFFREMENT DU MESSAGE POUR UN APPAREIL (RFC 8291)
// ======================================================
function webpush_encrypt($payload, $uaPublicB64, $authB64)
{
    $uaPublic = b64url_decode($uaPublicB64);
    $authSecret = b64url_decode($authB64);

    // Clé éphémère du serveur
    $opts = openssl_options(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $asKey = openssl_pkey_new($opts);
    $d = openssl_pkey_get_details($asKey)['ec'];
    $asPublic = "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);

    // Secret partagé ECDH
    $cleAppareil = ec_public_from_raw($uaPublic);
    $ecdh = $cleAppareil ? openssl_pkey_derive($cleAppareil, $asKey, 32) : false;

    if (!$ecdh) {
        throw new RuntimeException("Clé de l'appareil invalide");
    }

    $salt = random_bytes(16);

    $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $uaPublic . $asPublic, $authSecret);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

    // 0x02 = délimiteur du dernier (et unique) bloc
    $chiffre = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

    // En-tête : sel (16) | taille de bloc (4) | longueur de la clé (1) | clé publique (65)
    return $salt . pack('N', 4096) . chr(65) . $asPublic . $chiffre . $tag;
}

// ======================================================
// JETON VAPID (JWT signé ES256) POUR UN SERVICE PUSH
// ======================================================
function vapid_jwt($endpoint)
{
    $p = parse_url($endpoint);

    $entete = b64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $corps = b64url_encode(json_encode([
        'aud' => $p['scheme'] . '://' . $p['host'],
        'exp' => time() + 12 * 3600,
        'sub' => 'mailto:contact@bookpro.local',
    ]));

    openssl_sign("$entete.$corps", $der, app_secrets()['vapid_private_pem'], OPENSSL_ALGO_SHA256);

    return "$entete.$corps." . b64url_encode(der_to_raw_signature($der));
}

// Signature ECDSA : format DER (OpenSSL) => r||s sur 64 octets (JWT)
function der_to_raw_signature($der)
{
    $pos = 2;
    $raw = '';

    for ($i = 0; $i < 2; $i++) {
        $len = ord($der[$pos + 1]);
        $int = ltrim(substr($der, $pos + 2, $len), "\0");
        $raw .= str_pad($int, 32, "\0", STR_PAD_LEFT);
        $pos += 2 + $len;
    }

    return $raw;
}

// ======================================================
// ENVOI À UN APPAREIL. Renvoie le code HTTP du service push.
// ======================================================
function webpush_send(array $abonnement, array $message)
{
    $corps = webpush_encrypt(json_encode($message, JSON_UNESCAPED_UNICODE), $abonnement['p256dh'], $abonnement['auth']);

    $ch = curl_init($abonnement['endpoint']);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $corps,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Urgency: high',
            'Authorization: vapid t=' . vapid_jwt($abonnement['endpoint']) . ', k=' . vapid_public_key(),
        ],
    ]);

    // WAMP n'a pas de liste d'autorités de certification : on utilise celle
    // de Windows (la vérification TLS reste active)
    if (PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }

    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code === 0) {
        error_log("[BookPro] Push : " . curl_error($ch));
    }

    return $code;
}

// ======================================================
// NOTIFIER TOUS LES APPAREILS D'UN UTILISATEUR
// (abonnements expirés supprimés automatiquement)
// ======================================================
function notifier_utilisateur(PDO $pdo, $userId, array $message)
{
    $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);

    foreach ($stmt->fetchAll() as $abonnement) {

        try {
            $code = webpush_send($abonnement, $message);
        } catch (Throwable $ex) {
            error_log("[BookPro] Push échoué : " . $ex->getMessage());
            continue;
        }

        // 404 / 410 : l'appareil s'est désabonné
        if (in_array($code, [404, 410], true)) {
            $pdo->prepare("DELETE FROM push_subscriptions WHERE id = ?")->execute([$abonnement['id']]);
        } elseif ($code >= 400 || $code === 0) {
            error_log("[BookPro] Push refusé (HTTP $code) pour l'abonnement {$abonnement['id']}");
        }
    }
}
