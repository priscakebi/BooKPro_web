<?php
session_start();
require("../config/database.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET["id"])) {

    $id = (int) $_GET["id"];

    // Vérifie que le service appartient au professionnel connecté
    $stmt = $pdo->prepare("
        DELETE FROM services
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $id,
        $_SESSION["user_id"]
    ]);
}

header("Location: pro-dashboard.php");
exit();