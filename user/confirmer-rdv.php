<?php
session_start();
require("../config/database.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if (!isset($_POST["disponibilite_id"]) || !isset($_POST["service_id"])) {
    die("Données invalides");
}

$dispo_id = (int) $_POST["disponibilite_id"];
$service_id = (int) $_POST["service_id"];

/* 1. vérifier que le créneau existe */
$stmt = $pdo->prepare("
    SELECT *
    FROM disponibilites
    WHERE id = ? AND service_id = ?
");

$stmt->execute([$dispo_id, $service_id]);
$dispo = $stmt->fetch();

if (!$dispo) {
    die("Créneau invalide");
}

/* 2. vérifier qu’il n’est pas déjà réservé */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reservations
    WHERE disponibilite_id = ?
    AND statut IN ('en_attente', 'confirmee')
");

$stmt->execute([$dispo_id]);

if ($stmt->fetchColumn() > 0) {
    die("Créneau déjà réservé");
}

/* 3. créer réservation */
$stmt = $pdo->prepare("
    INSERT INTO reservations (user_id, disponibilite_id, statut)
    VALUES (?, ?, 'en_attente')
");

$stmt->execute([$user_id, $dispo_id]);

header("Location: reservations.php?success=1");
exit();
?>