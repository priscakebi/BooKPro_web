<?php
// ======================================================
// SOCLE COMMUN DES POINTS D'ENTRÉE JSON DE L'APP PRO
// ======================================================

require_once __DIR__ . "/../../includes/auth.php";
require __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/reservations.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Professionnel connecté obligatoire (réponse JSON, pas de redirection)
if (($_SESSION['role'] ?? '') !== 'professionnel') {
    json_out(['erreur' => 'non_connecte'], 401);
}

$proId = (int) $_SESSION['user_id'];

// Corps JSON des requêtes POST
$entree = json_decode(file_get_contents('php://input'), true) ?: [];

// Requêtes POST de la page : jeton CSRF dans l'en-tête X-CSRF-Token
function api_csrf_check()
{
    if (!hash_equals(csrf_token(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        json_out(['erreur' => 'csrf'], 400);
    }
}
