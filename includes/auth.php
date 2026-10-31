<?php
// ======================================================
// FONCTIONS COMMUNES : SESSION, ACCÈS, CSRF, ÉCHAPPEMENT
// ======================================================

date_default_timezone_set('Europe/Paris');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================================================
// URL DE BASE DU SITE (ex : /BookPro_web/BooKPro_web)
// Calculée automatiquement pour que les liens marchent
// quel que soit le dossier d'installation.
// ======================================================
if (!defined('BASE_URL')) {
    $root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $app  = str_replace('\\', '/', realpath(dirname(__DIR__)));
    define('BASE_URL', rtrim(substr($app, strlen($root)), '/'));
}

// ======================================================
// ÉCHAPPEMENT HTML (à utiliser pour TOUT affichage)
// ======================================================
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ======================================================
// REDIRECTION + MESSAGE FLASH (affiché par alertes.php)
// ======================================================
function redirect($path)
{
    header("Location: " . BASE_URL . $path);
    exit();
}

function flash($message, $type = 'success')
{
    $_SESSION['message'] = $message;
    $_SESSION['type'] = $type;
}

// ======================================================
// ESPACE PERSONNEL SELON LE RÔLE
// ======================================================
function dashboard_path($role)
{
    switch ($role) {
        case 'admin':
            return '/admin/dashboard.php';
        case 'professionnel':
            return '/pro/pro-dashboard.php';
        default:
            return '/user/dashboard.php';
    }
}

// ======================================================
// STATUTS DES RÉSERVATIONS (libellé + couleur Bootstrap)
// ======================================================
function statut_badge($statut)
{
    $statuts = [
        'en_attente' => ['En attente', 'warning'],
        'confirmee'  => ['Confirmée', 'success'],
        'refusee'    => ['Refusée', 'danger'],
        'annulee'    => ['Annulée', 'secondary'],
    ];

    [$label, $color] = $statuts[$statut] ?? [$statut, 'light'];

    return '<span class="badge bg-' . $color . '">' . e($label) . '</span>';
}

// ======================================================
// FORMATAGE DATE / HEURE
// ======================================================
function date_fr($date)
{
    return date('d/m/Y', strtotime($date));
}

function heure($time)
{
    return substr($time, 0, 5);
}

// ======================================================
// PROTECTION DES PAGES
// $role : null (connecté suffit), 'client', 'professionnel', 'admin'
// ======================================================
function require_login($role = null)
{
    if (!isset($_SESSION['user_id'])) {
        flash("Connectez-vous pour accéder à cette page.", 'warning');
        redirect("/auth/login.php");
    }

    // Mauvais rôle : on renvoie vers son propre espace
    if ($role !== null && ($_SESSION['role'] ?? '') !== $role) {
        flash("Cette page n'est pas accessible avec votre compte.", 'warning');
        redirect(dashboard_path($_SESSION['role'] ?? ''));
    }
}

// ======================================================
// CSRF : jeton à mettre dans chaque formulaire POST
// ======================================================
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    if (!hash_equals(csrf_token(), $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit("Requête invalide (jeton CSRF).");
    }
}
