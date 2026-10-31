<?php
// ======================================================
// SESSION + SECURITE (clients uniquement)
// ======================================================
require_once("../includes/auth.php");

require_login('client');

require("../config/database.php");
require("../includes/pros.php");

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
    SELECT r.statut, d.date_disponible, d.heure_debut, d.heure_fin, s.nom_service, s.prix,
           u.id AS pro_id, u.nom, u.entreprise, u.photo, u.adresse, u.code_postal, u.ville
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

// Suggestions de pros disponibles
$suggestions = rechercher_pros($pdo, [], 3);

$pageTitle = "Bonjour " . explode(' ', $_SESSION['user']['nom'])[0] . " 👋";
$pageSubtitle = "Retrouvez vos rendez-vous et réservez en quelques clics.";
$active = "dashboard";
$topAction = '<a href="services.php" class="btn btn-primary"><i class="bi bi-search me-1"></i> Trouver un pro</a>';
include("../includes/dash_header.php");
?>

<!-- ======================================================
STATS
====================================================== -->
<div class="row g-3 mb-4">

    <?php
    $cartes = [
        ['bi-calendar-check', '', (int) $total, 'Réservations au total'],
        ['bi-hourglass-split', 'amber', (int) ($stats['en_attente'] ?? 0), 'En attente de confirmation'],
        ['bi-check-circle', 'teal', (int) ($stats['confirmee'] ?? 0), 'Confirmées à venir'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $couleur, $valeur, $label]): ?>
        <div class="col-md-4">
            <div class="bp-card stat-card">
                <div class="stat-icon <?= $couleur ?>"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value" data-count="<?= $valeur ?>"><?= $valeur ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<!-- ======================================================
PROCHAIN RENDEZ-VOUS
====================================================== -->
<div class="bp-card mb-4">

    <h5 class="mb-3">Prochain rendez-vous</h5>

    <?php if ($prochain): ?>

        <div class="d-flex flex-wrap align-items-center gap-4">

            <div class="text-center px-3 py-2 rounded-4" style="background: var(--bp-primary-soft); min-width: 96px">
                <small class="text-uppercase fw-bold text-primary"><?= e(explode(' ', jour_court($prochain['date_disponible']))[0]) ?></small>
                <div class="fs-2 fw-bold lh-1"><?= date('j', strtotime($prochain['date_disponible'])) ?></div>
                <small class="text-muted"><?= e(explode(' ', jour_court($prochain['date_disponible']))[2]) ?></small>
            </div>

            <div class="flex-grow-1">
                <h5 class="mb-1"><?= e($prochain['nom_service']) ?> <?= statut_badge($prochain['statut']) ?></h5>
                <p class="mb-1"><i class="bi bi-clock me-1"></i> <?= heure($prochain['heure_debut']) ?> - <?= heure($prochain['heure_fin']) ?> · <?= prix($prochain['prix']) ?></p>
                <p class="text-muted mb-0 small"><i class="bi bi-geo-alt me-1"></i> <?= e(trim($prochain['adresse'] . ' ' . $prochain['code_postal'] . ' ' . $prochain['ville'])) ?></p>
            </div>

            <a href="pro.php?id=<?= (int) $prochain['pro_id'] ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <?= avatar($prochain, 44) ?>
                <strong><?= e($prochain['entreprise'] ?: $prochain['nom']) ?></strong>
            </a>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="bi bi-calendar-plus"></i>
            Aucun rendez-vous prévu.
        </div>

    <?php endif; ?>

</div>

<!-- ======================================================
SUGGESTIONS
====================================================== -->
<?php if ($suggestions): ?>

    <h5 class="mb-3">Disponibles prochainement</h5>

    <div class="row g-4">
        <?php foreach ($suggestions as $pro): ?>
            <div class="col-md-6 col-xl-4"><?php include("../includes/pro_card.php"); ?></div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php include("../includes/dash_footer.php"); ?>
