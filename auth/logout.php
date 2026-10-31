<?php
session_start();

// Un pro revient sur la connexion pro, les autres sur la connexion client
$page = ($_SESSION['role'] ?? '') === 'professionnel' ? 'pro-login.php' : 'login.php';

// Vide la session et supprime le cookie
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

session_destroy();

// Redirection vers la page de connexion
header("Location: " . $page);
exit();
?>