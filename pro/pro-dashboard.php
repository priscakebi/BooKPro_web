<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

// ======================================================
// STATISTIQUES
// ======================================================

$stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE user_id = ?");
$stmt->execute([$proId]);
$nbServices = $stmt->fetchColumn();

$stmt = $pdo->prepare("

    SELECT r.statut, COUNT(*) AS nb
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND d.date_disponible >= CURDATE()
    GROUP BY r.statut

");
$stmt->execute([$proId]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// ======================================================
// PROCHAINES RÉSERVATIONS (en attente + confirmées)
// ======================================================

$stmt = $pdo->prepare("

    SELECT r.statut, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, u.nom AS client
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ?
      AND d.date_disponible >= CURDATE()
      AND r.statut IN ('en_attente', 'confirmee')
    ORDER BY d.date_disponible, d.heure_debut
    LIMIT 5

");
$stmt->execute([$proId]);
$prochaines = $stmt->fetchAll();

$pageTitle = "Tableau de bord professionnel";
$active = "dashboard";
include("../includes/dash_header.php");

?>

<!-- ======================================================
STATS
====================================================== -->
<div class="row g-3">

    <div class="col-md-4">
        <div class="card stat-card">
            <i class="bi bi-briefcase"></i>
            <h4><?= (int) $nbServices ?></h4>
            <p>Services</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card">
            <i class="bi bi-hourglass-split"></i>
            <h4><?= (int) ($stats['en_attente'] ?? 0) ?></h4>
            <p>À confirmer</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card">
            <i class="bi bi-check-circle"></i>
            <h4><?= (int) ($stats['confirmee'] ?? 0) ?></h4>
            <p>Confirmées à venir</p>
        </div>
    </div>

</div>

<!-- ======================================================
PROCHAINES RÉSERVATIONS
====================================================== -->
<div class="card mt-4 p-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Prochaines réservations</h4>
        <a href="rdv.php" class="btn btn-outline-primary btn-sm">Tout voir</a>
    </div>

    <?php if (empty($prochaines)): ?>

        <p class="text-muted mb-0">Aucune réservation à venir.</p>

    <?php else: ?>

        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Date</th><th>Créneau</th><th>Service</th><th>Client</th><th>Statut</th></tr>
            </thead>
            <tbody>
            <?php foreach ($prochaines as $r): ?>
                <tr>
                    <td><?= date_fr($r['date_disponible']) ?></td>
                    <td><?= heure($r['heure_debut']) ?> - <?= heure($r['heure_fin']) ?></td>
                    <td><?= e($r['nom_service']) ?></td>
                    <td><?= e($r['client']) ?></td>
                    <td><?= statut_badge($r['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
