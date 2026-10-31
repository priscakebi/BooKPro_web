<?php
// ======================================================
// ABONNEMENT / DÉSABONNEMENT AUX NOTIFICATIONS PUSH
// Pour les applications pro et client.
// POST { action: "subscribe" | "unsubscribe", subscription: {...} }
// En-tête X-CSRF-Token obligatoire.
// ======================================================

require_once __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/webpush.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['professionnel', 'client'], true)) {
    json_out(['erreur' => 'non_connecte'], 401);
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['vapid' => vapid_public_key()]);
}

if (!hash_equals(csrf_token(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    json_out(['erreur' => 'csrf'], 400);
}

$entree = json_decode(file_get_contents('php://input'), true) ?: [];
$sub = $entree['subscription'] ?? [];
$endpoint = $sub['endpoint'] ?? '';
$hash = hash('sha256', $endpoint);

// Seuls les services push des navigateurs sont acceptés (https)
if (!preg_match('#^https://[^\s]+$#', $endpoint) || strlen($endpoint) > 2000) {
    json_out(['erreur' => 'abonnement_invalide'], 400);
}

if (($entree['action'] ?? '') === 'unsubscribe') {
    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint_hash = ? AND user_id = ?")->execute([$hash, $userId]);
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
")->execute([$userId, $endpoint, $hash, $p256dh, $auth]);

json_out(['ok' => true]);
