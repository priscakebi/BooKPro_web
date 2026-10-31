<?php
// ======================================================
// FONCTIONS COMMUNES : SESSION, ACCÈS, CSRF, ÉCHAPPEMENT
// ======================================================

date_default_timezone_set('Europe/Paris');

if (session_status() === PHP_SESSION_NONE) {
    // Cookie de session inaccessible au JavaScript et non envoyé par d'autres sites
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ======================================================
// RÉFÉRENTIELS
// ======================================================

// Métier => icône Bootstrap Icons
const METIERS = [
    'Plombier'    => 'bi-droplet-half',
    'Électricien' => 'bi-lightning-charge',
    'Mécanicien'  => 'bi-car-front',
    'Menuisier'   => 'bi-hammer',
    'Peintre'     => 'bi-palette',
];

// 1 = lundi ... 7 = dimanche (comme date('N'))
const JOURS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

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

// "lun. 15 déc." (sans dépendre de l'extension intl)
function jour_court($date)
{
    $jours = ['', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'];
    $mois = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($date);

    return $jours[date('N', $t)] . ' ' . date('j', $t) . ' ' . $mois[date('n', $t)];
}

// Lundi de la semaine d'une date (Y-m-d)
function lundi_de($date)
{
    $t = strtotime($date);

    return date('Y-m-d', strtotime('-' . (date('N', $t) - 1) . ' days', $t));
}

// Prix : 56.00 => "56 €", 56.50 => "56,50 €"
function prix($montant)
{
    $montant = (float) $montant;

    return (floor($montant) == $montant ? number_format($montant, 0, ',', ' ') : number_format($montant, 2, ',', ' ')) . ' €';
}

// ======================================================
// AVATAR : photo du pro, sinon initiales sur fond dégradé
// ======================================================
function avatar($user, $size = 56)
{
    $style = "width:{$size}px;height:{$size}px;font-size:" . round($size * 0.38) . "px";

    if (!empty($user['photo'])) {
        return '<img src="' . BASE_URL . '/uploads/pros/' . e($user['photo']) . '" alt="" class="avatar" style="' . $style . '">';
    }

    $mots = preg_split('/\s+/', trim($user['nom'] ?? '?'));
    $initiales = mb_strtoupper(mb_substr($mots[0], 0, 1) . (isset($mots[1]) ? mb_substr($mots[1], 0, 1) : ''));

    // Couleur stable par personne
    $teinte = crc32($user['nom'] ?? '') % 360;

    return '<span class="avatar avatar-initiales" style="' . $style . ';--h:' . $teinte . '">' . e($initiales) . '</span>';
}

// Étoiles pleines / vides pour une note sur 5
function etoiles($note)
{
    $n = (int) round((float) $note);

    return '<span class="stars">' . str_repeat('<i class="bi bi-star-fill"></i>', $n) . str_repeat('<i class="bi bi-star"></i>', 5 - $n) . '</span>';
}

// ======================================================
// OUVERTURE DE SESSION (connexion ou juste après inscription)
// ======================================================
function login_user($user)
{
    // Nouvel identifiant de session (anti-fixation)
    session_regenerate_id(true);

    // On ne garde pas le hash du mot de passe en session
    unset($user["password"]);

    $_SESSION["user"] = $user;
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];
}

// Redirection "après connexion" : uniquement un chemin interne (/...)
function safe_next($next)
{
    return (is_string($next) && preg_match('#^/[^/\\\\]#', $next)) ? $next : null;
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
