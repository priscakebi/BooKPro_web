<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

// ======================================================
// PROFIL : étapes de démarrage
// ======================================================

$stmt = $pdo->prepare("
    SELECT u.description, u.photo,
           (SELECT COUNT(*) FROM services WHERE user_id = u.id) AS nb_services,
           (SELECT COUNT(*) FROM horaires_ouverture WHERE user_id = u.id) AS nb_horaires,
           (SELECT COUNT(*) FROM disponibilites d JOIN services s ON d.service_id = s.id
             WHERE s.user_id = u.id AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
               AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)) AS nb_libres
    FROM users u WHERE u.id = ?
");
$stmt->execute([$proId]);
$p = $stmt->fetch();

$etapes = [
    ['Ajouter une présentation et une photo', 'profil.php', $p['description'] && $p['photo']],
    ['Publier vos services et tarifs', 'services.php', $p['nb_services'] > 0],
    ["Renseigner vos horaires d'ouverture", 'ouverture.php', $p['nb_horaires'] > 0],
    ['Ouvrir des créneaux à la réservation', 'horaires.php', $p['nb_libres'] > 0],
];
$faites = count(array_filter(array_column($etapes, 2)));
$pourcent = round($faites / count($etapes) * 100);

// ======================================================
// STATISTIQUES
// ======================================================

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

// Chiffre d'affaires des RDV confirmés du mois en cours
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(s.prix), 0)
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND r.statut = 'confirmee'
      AND d.date_disponible BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())
");
$stmt->execute([$proId]);
$caMois = $stmt->fetchColumn();

// ======================================================
// PROCHAINES RÉSERVATIONS
// ======================================================

$stmt = $pdo->prepare("
    SELECT r.statut, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, s.prix, u.nom AS client
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ?
      AND d.date_disponible >= CURDATE()
      AND r.statut IN ('en_attente', 'confirmee')
    ORDER BY d.date_disponible, d.heure_debut
    LIMIT 6
");
$stmt->execute([$proId]);
$prochaines = $stmt->fetchAll();

$pageTitle = "Bonjour " . explode(' ', $_SESSION['user']['nom'])[0] . " 👋";
$pageSubtitle = "Voici l'activité de votre entreprise.";
$active = "dashboard";
include("../includes/dash_header.php");

?>

<!-- ======================================================
DÉMARRAGE (masqué une fois tout fait)
====================================================== -->
<?php if ($faites < count($etapes)): ?>
<div class="bp-card mb-4" style="background: linear-gradient(135deg, #f4f2ff, #ecfdf8);">

    <div class="d-flex align-items-center gap-4 flex-wrap">

        <div class="progress-ring" style="--p: <?= $pourcent ?>"><span><?= $pourcent ?>%</span></div>

        <div class="flex-grow-1">
            <h5 class="mb-1">Complétez votre profil</h5>
            <p class="text-muted mb-2 small">Les clients ne voient votre page que lorsque vous avez publié au moins un service. Ils ne peuvent réserver que s'il y a des créneaux.</p>

            <div class="row">
                <?php foreach ($etapes as [$label, $lien, $ok]): ?>
                    <div class="col-md-6">
                        <a href="<?= $lien ?>" class="check-item <?= $ok ? 'done' : '' ?>">
                            <i class="bi <?= $ok ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i> <?= $label ?>
                            <?php if (!$ok): ?><i class="bi bi-arrow-right small ms-auto text-primary"></i><?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>
<?php endif; ?>

<!-- ======================================================
STATS
====================================================== -->
<div class="row g-3 mb-4">

    <?php
    $cartes = [
        ['bi-hourglass-split', 'amber', (int) ($stats['en_attente'] ?? 0), 'À confirmer', 'rdv.php'],
        ['bi-calendar-check', 'teal', (int) ($stats['confirmee'] ?? 0), 'RDV confirmés à venir', 'rdv.php'],
        ['bi-calendar-plus', '', (int) $p['nb_libres'], 'Créneaux libres', 'horaires.php'],
        ['bi-currency-euro', 'pink', prix($caMois), 'Confirmé ce mois-ci', 'rdv.php'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $couleur, $valeur, $label, $lien]): ?>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= $lien ?>" class="bp-card card-hover stat-card text-decoration-none text-dark">
                <div class="stat-icon <?= $couleur ?>"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value"><?= e($valeur) ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

</div>

<!-- ======================================================
PROCHAINES RÉSERVATIONS
====================================================== -->
<div class="bp-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Prochains rendez-vous</h5>
        <a href="rdv.php" class="btn btn-soft btn-sm">Tout voir</a>
    </div>

    <?php if (empty($prochaines)): ?>

        <div class="empty-state">
            <i class="bi bi-calendar-heart"></i>
            Aucun rendez-vous à venir. Publiez des créneaux pour recevoir des réservations.
        </div>

    <?php else: ?>

        <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr><th>Date</th><th>Créneau</th><th>Service</th><th>Client</th><th>Statut</th></tr>
            </thead>
            <tbody>
            <?php foreach ($prochaines as $r): ?>
                <tr>
                    <td class="fw-semibold text-capitalize"><?= e(jour_court($r['date_disponible'])) ?></td>
                    <td><?= heure($r['heure_debut']) ?> - <?= heure($r['heure_fin']) ?></td>
                    <td><?= e($r['nom_service']) ?> <small class="text-muted">· <?= prix($r['prix']) ?></small></td>
                    <td><?= e($r['client']) ?></td>
                    <td><?= statut_badge($r['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
