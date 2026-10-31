<?php

// ======================================================
// RÉSERVATION D'UN CRÉNEAU (formulaire de la page du pro)
// ======================================================

require_once("../includes/auth.php");

require_login('client');

require("../config/database.php");
require_once("../config/mail.php");
require_once("../includes/reservations.php");
require_once("../includes/conges.php");

// Anciens liens ?service=ID : on renvoie vers la page du pro
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $stmt = $pdo->prepare("SELECT user_id FROM services WHERE id = ?");
    $stmt->execute([(int) ($_GET['service'] ?? 0)]);
    $proId = $stmt->fetchColumn();

    redirect($proId ? "/user/pro.php?id=$proId&service=" . (int) $_GET['service'] . "#reserver" : "/user/services.php");
}

csrf_check();

// ======================================================
// LE CRÉNEAU DOIT EXISTER ET ÊTRE À VENIR
// ======================================================

$stmt = $pdo->prepare("

    SELECT d.*, s.nom_service, s.user_id AS pro_id, u.email AS pro_email
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE d.id = ?
      AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
      AND " . SQL_HORS_CONGES . "

");
$stmt->execute([(int) ($_POST['disponibilite_id'] ?? 0)]);
$dispo = $stmt->fetch();

if (!$dispo) {
    flash("Ce créneau n'est plus disponible.", "danger");
    redirect("/user/services.php");
}

$retour = "/user/pro.php?id={$dispo['pro_id']}&service={$dispo['service_id']}#reserver";

try {

    // L'index UNIQUE (creneau_actif) empêche deux réservations actives
    // sur le même créneau, même si deux clients cliquent en même temps
    $pdo->prepare("INSERT INTO reservations (user_id, disponibilite_id, statut) VALUES (?, ?, 'en_attente')")
        ->execute([$_SESSION['user_id'], $dispo['id']]);

    $resaId = (int) $pdo->lastInsertId();

} catch (PDOException $ex) {

    // 23000 = contrainte UNIQUE violée
    if ($ex->getCode() !== '23000') {
        throw $ex;
    }

    flash("Ce créneau vient d'être réservé par quelqu'un d'autre. Choisissez-en un autre.", "danger");
    redirect($retour);
}

$quand = date_fr($dispo['date_disponible']) . " à " . heure($dispo['heure_debut']);

// Mails client + professionnel
sendMail($_SESSION['email'], "Demande de réservation envoyée",
    "Votre demande pour « {$dispo['nom_service']} » le $quand a été envoyée. Le professionnel va la confirmer.");

sendMail($dispo['pro_email'], "Nouvelle réservation",
    "Nouvelle demande pour « {$dispo['nom_service']} » le $quand. Connectez-vous à BookPro pour la confirmer.");

// Notification instantanée sur les appareils du pro (boutons Confirmer / Refuser)
notifier_nouvelle_demande($pdo, $resaId);

flash("Demande envoyée pour le $quand ! Le professionnel doit maintenant la confirmer.");

redirect("/user/reservations.php");
