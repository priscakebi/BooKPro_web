<?php

// ======================================================
// SESSION + SECURITE (clients uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('client');

require("../config/database.php");
require("../config/mail.php");

// ======================================================
// SERVICE CHOISI (?service=ID depuis la page Services)
// ======================================================

$serviceId = (int) ($_GET['service'] ?? 0);

$stmt = $pdo->prepare("

    SELECT s.*, u.nom AS pro, u.email AS pro_email, u.metier, u.ville
    FROM services s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?

");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    flash("Choisissez d'abord un service.", "info");
    redirect("/user/services.php");
}

// ======================================================
// RÉSERVATION D'UN CRÉNEAU
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    // Le créneau doit appartenir à ce service et être à venir
    $stmt = $pdo->prepare("

        SELECT * FROM disponibilites
        WHERE id = ? AND service_id = ?
          AND CONCAT(date_disponible, ' ', heure_debut) > NOW()

    ");
    $stmt->execute([(int) ($_POST['disponibilite_id'] ?? 0), $serviceId]);
    $dispo = $stmt->fetch();

    if (!$dispo) {
        flash("Ce créneau n'est plus disponible.", "danger");
        redirect("/user/prendre_rdv.php?service=" . $serviceId);
    }

    try {

        // L'index UNIQUE (creneau_actif) empêche deux réservations actives
        // sur le même créneau, même si deux clients cliquent en même temps
        $pdo->prepare("INSERT INTO reservations (user_id, disponibilite_id, statut) VALUES (?, ?, 'en_attente')")
            ->execute([$_SESSION['user_id'], $dispo['id']]);

    } catch (PDOException $ex) {

        // 23000 = contrainte UNIQUE violée
        if ($ex->getCode() !== '23000') {
            throw $ex;
        }

        flash("Ce créneau vient d'être réservé par quelqu'un d'autre.", "danger");
        redirect("/user/prendre_rdv.php?service=" . $serviceId);
    }

    $quand = date_fr($dispo['date_disponible']) . " à " . heure($dispo['heure_debut']);

    // Mails client + professionnel
    sendMail($_SESSION['email'], "Demande de réservation envoyée",
        "Votre demande pour « {$service['nom_service']} » le $quand a été envoyée. Le professionnel va la confirmer.");

    sendMail($service['pro_email'], "Nouvelle réservation",
        "Nouvelle demande pour « {$service['nom_service']} » le $quand. Connectez-vous à BookPro pour la confirmer.");

    flash("Demande envoyée ! Le professionnel doit maintenant la confirmer.");

    redirect("/user/reservations.php");
}

// ======================================================
// CRÉNEAUX LIBRES DE CE SERVICE
// ======================================================

$stmt = $pdo->prepare("

    SELECT d.*
    FROM disponibilites d
    WHERE d.service_id = ?
      AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
      AND NOT EXISTS (
          SELECT 1 FROM reservations r
          WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL
      )
    ORDER BY d.date_disponible, d.heure_debut

");
$stmt->execute([$serviceId]);
$creneaux = $stmt->fetchAll();

$pageTitle = "Réserver : " . $service['nom_service'];
$active = "services";
include("../includes/dash_header.php");

?>

<div class="card p-4 mb-4">

    <h4><?= e($service['nom_service']) ?></h4>

    <p class="mb-2"><?= nl2br(e($service['description'])) ?></p>

    <p class="mb-0 text-muted">
        <i class="bi bi-person-fill"></i> <?= e($service['pro']) ?>
        <?php if ($service['metier']): ?> · <?= e($service['metier']) ?><?php endif; ?>
        <?php if ($service['ville']): ?> · <i class="bi bi-geo-alt"></i> <?= e($service['ville']) ?><?php endif; ?>
        · <strong><?= e($service['prix']) ?> €</strong>
        <?php if ($service['duree']): ?> · <?= (int) $service['duree'] ?> min<?php endif; ?>
    </p>

</div>

<div class="card p-4">

    <h5>Créneaux disponibles</h5>

    <?php if (empty($creneaux)): ?>

        <p class="text-muted mb-0">Aucun créneau disponible pour le moment. Revenez plus tard !</p>

    <?php else: ?>

        <div class="d-flex flex-wrap gap-2">

        <?php foreach ($creneaux as $c): ?>

            <form method="POST" onsubmit="return confirm('Réserver ce créneau ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="disponibilite_id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-outline-primary">
                    <?= date_fr($c['date_disponible']) ?><br>
                    <small><?= heure($c['heure_debut']) ?> - <?= heure($c['heure_fin']) ?></small>
                </button>
            </form>

        <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<a href="services.php" class="btn btn-link mt-3">← Retour aux services</a>

<?php include("../includes/dash_footer.php"); ?>
