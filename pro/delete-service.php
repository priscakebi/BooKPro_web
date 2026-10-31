<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

// ======================================================
// SUPPRESSION : uniquement en POST, avec CSRF,
// et uniquement un service du pro connecté
// ======================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

csrf_check();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    flash("Service introuvable.", "danger");
    redirect("/pro/services.php");
}

// Refus si des clients ont une réservation active à venir
$stmt = $pdo->prepare("

    SELECT COUNT(*)
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    WHERE d.service_id = ?
      AND d.date_disponible >= CURDATE()
      AND r.statut IN ('en_attente', 'confirmee')

");
$stmt->execute([$id]);

if ($stmt->fetchColumn() > 0) {
    flash("Ce service a des réservations à venir : annulez-les avant de le supprimer.", "danger");
    redirect("/pro/services.php");
}

// Suppression : réservations passées, puis le service
// (les disponibilités et avis suivent par clés étrangères)
$pdo->beginTransaction();

$pdo->prepare("

    DELETE r FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    WHERE d.service_id = ?

")->execute([$id]);

$pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);

$pdo->commit();

flash("Service supprimé.");

redirect("/pro/services.php");
