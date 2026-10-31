<?php
// ======================================================
// ABONNEMENT / DÉSABONNEMENT AUX NOTIFICATIONS PUSH
// POST { action: "subscribe" | "unsubscribe", subscription: {...} }
// ======================================================

require __DIR__ . "/_api.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['vapid' => vapid_public_key()]);
}

api_csrf_check();

$sub = $entree['subscription'] ?? [];
$endpoint = $sub['endpoint'] ?? '';
$hash = hash('sha256', $endpoint);

// Seuls les services push des navigateurs sont acceptés (https)
if (!preg_match('#^https://[^\s]+$#', $endpoint) || strlen($endpoint) > 2000) {
    json_out(['erreur' => 'abonnement_invalide'], 400);
}

if (($entree['action'] ?? '') === 'unsubscribe') {
    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ? AND user_id = ?")->execute([$hash, $proId]);
    json_out(['ok' => true]);
}

$p256dh = $sub['keys']['p256dh'] ?? '';
$auth = $sub['keys']['auth'] ?? '';

// La clé doit être un vrai point de la courbe P-256 (sinon le chiffrement échouerait)
if (strlen(b64url_decode($p256dh)) !== 65 || strlen(b64url_decode($auth)) !== 16 || !ec_public_from_raw(b64url_decode($p256dh))) {
    json_out(['erreur' => 'cles_invalides'], 400);
}

// Un appareil appartient au dernier compte qui s'y est abonné
$pdo->prepare("
    INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, p256dh, auth)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)
")->execute([$proId, $endpoint, $hash, $p256dh, $auth]);

json_out(['ok' => true]);
