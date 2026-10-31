<?php
// ======================================================
// LIEN COURT DU PRO (/p/mon-entreprise) ET VISITES DE SA PAGE
// ======================================================

const SOURCES_VISITE = ['qr' => 'QR code', 'lien' => 'Lien partagé', 'bouton' => 'Bouton sur votre site', 'site' => 'Recherche BookPro'];

// "Dupont Plomberie - Lyon" => "dupont-plomberie-lyon"
function slugifier($texte)
{
    $texte = strtr(mb_strtolower(trim($texte)), [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae', "'" => '-', '’' => '-',
    ]);

    return trim(preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
}

// Format accepté pour un lien personnalisé
function slug_valide($slug)
{
    return (bool) preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) && strlen($slug) >= 3 && strlen($slug) <= 60;
}

// Lien court du pro (créé à la première demande s'il n'existe pas)
function slug_pro(PDO $pdo, array $pro)
{
    if (!empty($pro['slug'])) {
        return $pro['slug'];
    }

    $base = slugifier($pro['entreprise'] ?: $pro['nom']) ?: 'pro';
    $base = substr($base, 0, 50);

    // Premier libre parmi : base, base-ville, base-2, base-3...
    $candidats = [$base];
    if (!empty($pro['ville'])) {
        $candidats[] = substr($base . '-' . slugifier($pro['ville']), 0, 60);
    }
    for ($i = 2; $i < 100; $i++) {
        $candidats[] = $base . '-' . $i;
    }

    $existe = $pdo->prepare("SELECT 1 FROM users WHERE slug = ?");

    foreach ($candidats as $slug) {
        if (strlen($slug) < 3) {
            continue;
        }

        $existe->execute([$slug]);

        if (!$existe->fetch()) {
            $pdo->prepare("UPDATE users SET slug = ? WHERE id = ? AND slug IS NULL")->execute([$slug, $pro['id']]);
            return $slug;
        }
    }

    return null;
}

// Adresse publique complète du lien court
function url_courte($slug, $source = null)
{
    require_once __DIR__ . '/../config/app.php';

    // En ligne de commande, l'adresse vient de la config ; sinon de la requête
    $origine = PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])
        ? APP_URL
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL);

    return $origine . '/p/' . $slug . ($source ? '?src=' . $source : '');
}

// Compte une visite de la page publique (pas celles du pro lui-même)
function compter_visite(PDO $pdo, $proId, $source)
{
    if (($_SESSION['user_id'] ?? null) == $proId || !isset(SOURCES_VISITE[$source])) {
        return;
    }

    // Une seule visite comptée par pro et par session
    $cle = 'vu_' . $proId;
    if (!empty($_SESSION[$cle])) {
        return;
    }
    $_SESSION[$cle] = true;

    $pdo->prepare("
        INSERT INTO vues_pro (pro_id, jour, source, nb) VALUES (?, CURDATE(), ?, 1)
        ON DUPLICATE KEY UPDATE nb = nb + 1
    ")->execute([$proId, $source]);
}
