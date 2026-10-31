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
    WHERE r.user_id = ? AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
    GROUP BY r.statut
");
$stmt->execute([$userId]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM reservations r JOIN disponibilites d ON r.disponibilite_id = d.id
    WHERE r.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible < CURDATE()
");
$stmt->execute([$userId]);
$honores = $stmt->fetchColumn();

// ======================================================
// RENDEZ-VOUS À VENIR (le premier est mis en avant)
// ======================================================
$stmt = $pdo->prepare("
    SELECT r.id, r.statut, d.date_disponible, d.heure_debut, d.heure_fin, s.nom_service, s.prix,
           u.id AS pro_id, u.nom, u.entreprise, u.photo, u.adresse, u.code_postal, u.ville, u.telephone
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.user_id = ?
      AND r.statut IN ('en_attente', 'confirmee')
      AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
    ORDER BY d.date_disponible, d.heure_debut
    LIMIT 4
");
$stmt->execute([$userId]);
$aVenir = $stmt->fetchAll();
$prochain = $aVenir[0] ?? null;

// ======================================================
// AVIS À DONNER (RDV honorés sans avis, les plus récents)
// ======================================================
$stmt = $pdo->prepare("
    SELECT r.id, d.date_disponible, s.nom_service, u.nom, u.entreprise
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN avis a ON a.reservation_id = r.id
    WHERE r.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible < CURDATE() AND a.id IS NULL
      AND d.date_disponible >= CURDATE() - INTERVAL 60 DAY
    ORDER BY d.date_disponible DESC
    LIMIT 2
");
$stmt->execute([$userId]);
$avisADonner = $stmt->fetchAll();

// ======================================================
// MES PROFESSIONNELS (déjà consultés, pour reprendre RDV)
// ======================================================
$stmt = $pdo->prepare("
    SELECT u.id, u.nom, u.entreprise, u.metier, u.photo, u.ville,
           COUNT(*) AS visites, MAX(d.date_disponible) AS derniere
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.user_id = ? AND r.statut = 'confirmee' AND u.suspendu_at IS NULL
    GROUP BY u.id
    ORDER BY derniere DESC
    LIMIT 4
");
$stmt->execute([$userId]);
$mesPros = $stmt->fetchAll();

// Suggestions si aucun pro habituel
$suggestions = $mesPros ? [] : rechercher_pros($pdo, [], 3);

$pageTitle = "Bonjour " . explode(' ', $_SESSION['user']['nom'])[0] . " 👋";
$pageSubtitle = "Vos rendez-vous et vos professionnels, au même endroit.";
$active = "dashboard";
$topAction = '<a href="services.php" class="btn btn-primary"><i class="bi bi-search me-1"></i> Trouver un pro</a>';
include("../includes/dash_header.php");
?>

<?php include("../includes/app_card.php"); ?>

<!-- ======================================================
CHIFFRES
====================================================== -->
<div class="row g-3 mb-4">

    <?php
    $cartes = [
        ['bi-hourglass-split', 'amber', (int) ($stats['en_attente'] ?? 0), 'En attente de confirmation'],
        ['bi-calendar-check', 'teal', (int) ($stats['confirmee'] ?? 0), 'Rendez-vous confirmés à venir'],
        ['bi-check2-all', '', (int) $honores, 'Rendez-vous passés'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $couleur, $valeur, $label]): ?>
        <div class="col-md-4">
            <a href="reservations.php" class="bp-card card-hover stat-card text-decoration-none text-dark">
                <div class="stat-icon <?= $couleur ?>"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value" data-count="<?= $valeur ?>"><?= $valeur ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

</div>

<div class="row g-4">

    <div class="col-xl-8">

        <!-- PROCHAIN RENDEZ-VOUS -->
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
                        <?php $adresse = trim($prochain['adresse'] . ' ' . $prochain['code_postal'] . ' ' . $prochain['ville']); ?>
                        <?php if ($adresse): ?>
                            <a class="small text-decoration-none" target="_blank" rel="noopener"
                               href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($adresse) ?>">
                                <i class="bi bi-geo-alt me-1"></i><?= e($adresse) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <a href="pro.php?id=<?= (int) $prochain['pro_id'] ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                            <?= avatar($prochain, 40) ?>
                            <strong><?= e($prochain['entreprise'] ?: $prochain['nom']) ?></strong>
                        </a>
                        <?php if ($prochain['statut'] === 'confirmee' && $prochain['telephone']): ?>
                            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $prochain['telephone'])) ?>" class="btn btn-soft btn-sm"><i class="bi bi-telephone me-1"></i> Appeler</a>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if (count($aVenir) > 1): ?>
                    <div class="border-top mt-3 pt-3">
                        <p class="small text-muted mb-2">Ensuite</p>
                        <?php foreach (array_slice($aVenir, 1) as $r): ?>
                            <div class="d-flex justify-content-between small py-1">
                                <span><span class="text-capitalize-first"><?= e(jour_court($r['date_disponible'])) ?></span> · <?= heure($r['heure_debut']) ?> · <?= e($r['nom_service']) ?></span>
                                <?= statut_badge($r['statut']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>

                <div class="empty-state">
                    <i class="bi bi-calendar-plus"></i>
                    Aucun rendez-vous prévu.
                    <div class="mt-3"><a href="services.php" class="btn btn-primary">Réserver un professionnel</a></div>
                </div>

            <?php endif; ?>

        </div>

        <!-- MES PROFESSIONNELS -->
        <?php if ($mesPros): ?>
            <div class="bp-card">
                <h5 class="mb-3">Vos professionnels</h5>
                <div class="row g-3">
                    <?php foreach ($mesPros as $p): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3 p-3 rounded-4" style="background: var(--bp-bg)">
                                <?= avatar($p, 48) ?>
                                <div class="flex-grow-1 min-w-0">
                                    <strong class="d-block text-truncate"><?= e($p['entreprise'] ?: $p['nom']) ?></strong>
                                    <small class="text-muted"><?= e($p['metier']) ?> · <?= (int) $p['visites'] ?> RDV</small>
                                </div>
                                <a href="pro.php?id=<?= (int) $p['id'] ?>#reserver" class="btn btn-primary btn-sm text-nowrap">Reprendre RDV</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="col-xl-4">

        <!-- AVIS À DONNER -->
        <?php if ($avisADonner): ?>
            <div class="bp-card mb-4" style="border-left: 4px solid #f5b301;">
                <h5 class="mb-1"><i class="bi bi-star-fill text-warning me-1"></i> Votre avis compte</h5>
                <p class="small text-muted mb-3">Aidez les autres clients à choisir.</p>
                <?php foreach ($avisADonner as $a): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-top">
                        <div class="small">
                            <strong><?= e($a['entreprise'] ?: $a['nom']) ?></strong><br>
                            <span class="text-muted"><?= e($a['nom_service']) ?> · <?= date_fr($a['date_disponible']) ?></span>
                        </div>
                        <a href="reservations.php#avis<?= (int) $a['id'] ?>" class="btn btn-soft btn-sm">Noter</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- RECHERCHE RAPIDE -->
        <div class="bp-card">
            <h5 class="mb-3">Besoin d'un pro ?</h5>
            <div class="d-grid gap-2">
                <?php foreach (METIERS as $metier => $icon): ?>
                    <a href="services.php?metier=<?= urlencode($metier) ?>" class="chip justify-content-start"><i class="bi <?= $icon ?>"></i> <?= e($metier) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php if ($suggestions): ?>

    <h5 class="mt-4 mb-3">Disponibles prochainement</h5>

    <div class="row g-4">
        <?php foreach ($suggestions as $pro): ?>
            <div class="col-md-6 col-xl-4"><?php include("../includes/pro_card.php"); ?></div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php include("../includes/dash_footer.php"); ?>
