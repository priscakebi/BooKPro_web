<?php
// ======================================================
// SESSION + SECURITE (clients uniquement)
// ======================================================
require_once("../includes/auth.php");

require_login('client');

require("../config/database.php");

$userId = $_SESSION['user_id'];

// ======================================================
// STATISTIQUES (réservations à venir par statut)
// ======================================================
$stmt = $pdo->prepare("
    SELECT r.statut, COUNT(*) AS nb
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    WHERE r.user_id = ? AND d.date_disponible >= CURDATE()
    GROUP BY r.statut
");
$stmt->execute([$userId]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetchColumn();

// ======================================================
// PROCHAIN RENDEZ-VOUS
// ======================================================
$stmt = $pdo->prepare("
    SELECT r.statut, d.date_disponible, d.heure_debut, d.heure_fin, s.nom_service, u.nom AS pro
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.user_id = ?
      AND r.statut IN ('en_attente', 'confirmee')
      AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
    ORDER BY d.date_disponible, d.heure_debut
    LIMIT 1
");
$stmt->execute([$userId]);
$prochain = $stmt->fetch();

$pageTitle = "Tableau de bord";
$active = "dashboard";
include("../includes/dash_header.php");
?>

        <!-- ======================================================
        CARDS STATS
        ====================================================== -->
        <div class="row g-3">

            <div class="col-md-4">

                <div class="card stat-card">

                    <i class="bi bi-calendar-check"></i>

                    <h4><?= (int) $total ?></h4>

                    <p>Réservations</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card stat-card">

                    <i class="bi bi-hourglass-split"></i>

                    <h4><?= (int) ($stats['en_attente'] ?? 0) ?></h4>

                    <p>En attente</p>

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
        PROCHAIN RENDEZ-VOUS
        ====================================================== -->
        <div class="card mt-4 p-4">

            <h4>Bienvenue <?= e($_SESSION['user']['nom']) ?> 👋</h4>

            <?php if ($prochain): ?>

                <p class="mb-0">
                    Prochain rendez-vous : <strong><?= e($prochain['nom_service']) ?></strong>
                    avec <?= e($prochain['pro']) ?>,
                    le <?= date_fr($prochain['date_disponible']) ?>
                    de <?= heure($prochain['heure_debut']) ?> à <?= heure($prochain['heure_fin']) ?>
                    <?= statut_badge($prochain['statut']) ?>
                </p>

            <?php else: ?>

                <p class="text-muted">Aucun rendez-vous prévu.</p>

                <a href="services.php" class="btn btn-primary align-self-start">Trouver un professionnel</a>

            <?php endif; ?>

        </div>

<?php include("../includes/dash_footer.php"); ?>
