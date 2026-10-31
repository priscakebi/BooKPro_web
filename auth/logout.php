<?php
session_start();

// Vide la session et supprime le cookie
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

session_destroy();

// Redirection vers login
header("Location: login.php");
exit();
?>