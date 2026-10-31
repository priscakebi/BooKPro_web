<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/reservations.php");
require("../includes/charts.php");

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
           s.nom_service, s.prix, r.user_id AS client_id, u.nom AS client
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

// ======================================================
// À TRAITER, PROGRAMME DU JOUR
// ======================================================

$aTraiterListe = demandes_en_attente($pdo, $proId, 5);

$stmt = $pdo->prepare("
    SELECT d.heure_debut, d.heure_fin, s.nom_service, u.nom AS client, u.telephone, r.user_id AS client_id
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible = CURDATE()
    ORDER BY d.heure_debut
");
$stmt->execute([$proId]);
$aujourdhui = $stmt->fetchAll();

// ======================================================
// TENDANCE : chiffre d'affaires des 8 dernières semaines
// ======================================================

$lundiDebut = date('Y-m-d', strtotime(lundi_de(date('Y-m-d')) . ' -7 weeks'));

$stmt = $pdo->prepare("
    SELECT d.date_disponible, SUM(s.prix) FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible BETWEEN ? AND CURDATE()
    GROUP BY d.date_disponible
");
$stmt->execute([$proId, $lundiDebut]);

$semaines = [];
for ($i = 0; $i < 8; $i++) {
    $l = date('Y-m-d', strtotime("$lundiDebut +$i weeks"));
    $semaines[$l] = ['label' => date('d/m', strtotime($l)), 'tip' => 'Semaine du ' . jour_court($l), 'valeur' => 0];
}
foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $jour => $ca) {
    $semaines[lundi_de($jour)]['valeur'] += $ca;
}
$semaines[lundi_de(date('Y-m-d'))]['attenue'] = true;
$semaines[lundi_de(date('Y-m-d'))]['tip'] .= ' (en cours)';
$semaines = array_values($semaines);

// ======================================================
// DERNIERS AVIS
// ======================================================

$stmt = $pdo->prepare("
    SELECT a.note, a.commentaire, u.nom AS client FROM avis a
    JOIN reservations r ON a.reservation_id = r.id
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON a.user_id = u.id
    WHERE s.user_id = ? ORDER BY a.created_at DESC LIMIT 3
");
$stmt->execute([$proId]);
$derniersAvis = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT ROUND(AVG(a.note), 1) FROM avis a
    JOIN reservations r ON a.reservation_id = r.id
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ?
");
$stmt->execute([$proId]);
$noteMoyenne = $stmt->fetchColumn();

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

    <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">

        <div class="progress-ring" style="--p: <?= $pourcent ?>"><span><?= $pourcent ?>%</span></div>

        <div class="flex-grow-1 min-w-0">
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

<?php include("../includes/app_card.php"); ?>

<!-- ======================================================
CHIFFRES CLÉS
====================================================== -->
<div class="row g-3 mb-4">

    <?php
    $cartes = [
        ['bi-hourglass-split', 'amber', (int) ($stats['en_attente'] ?? 0), 'À confirmer', 'agenda.php'],
        ['bi-calendar-check', 'teal', (int) ($stats['confirmee'] ?? 0), 'RDV confirmés à venir', 'rdv.php'],
        ['bi-calendar-plus', '', (int) $p['nb_libres'], 'Créneaux libres', 'horaires.php'],
        ['bi-currency-euro', 'pink', prix($caMois), 'Confirmé ce mois-ci', 'stats.php'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $couleur, $valeur, $label, $lien]): ?>
        <div class="col-6 col-xl-3">
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

<div class="row g-4">

    <!-- ======================================================
    COLONNE PRINCIPALE
    ====================================================== -->
    <div class="col-xl-8">

        <!-- À TRAITER : confirmer / refuser sans quitter la page -->
        <?php if ($aTraiterListe): ?>
            <div class="bp-card mb-4" style="border-left: 4px solid #f5a524;">

                <h5 class="mb-3"><i class="bi bi-bell-fill text-warning me-1"></i> <?= count($aTraiterListe) ?> demande<?= count($aTraiterListe) > 1 ? 's' : '' ?> à traiter</h5>

                <?php foreach ($aTraiterListe as $d): ?>
                    <div class="d-flex flex-wrap align-items-center gap-3 py-2 border-bottom">
                        <?= avatar(['nom' => $d['client']], 38) ?>
                        <div class="flex-grow-1 min-w-0">
                            <a href="client.php?id=<?= (int) $d['client_id'] ?>" class="fw-bold text-decoration-none"><?= e($d['client']) ?></a> · <?= e($d['nom_service']) ?>
                            <div class="small text-muted text-capitalize-first"><?= e(jour_court($d['date_disponible'])) ?> · <?= heure($d['heure_debut']) ?> - <?= heure($d['heure_fin']) ?> · <?= prix($d['prix']) ?></div>
                        </div>
                        <div class="d-flex gap-2">
                            <?php foreach (['confirmer' => 'btn-success', 'refuser' => 'btn-outline-danger'] as $action => $style): ?>
                                <form method="POST" action="rdv.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $action ?>">
                                    <input type="hidden" name="retour" value="/pro/pro-dashboard.php">
                                    <button class="btn btn-sm <?= $style ?>"><?= $action === 'confirmer' ? '<i class="bi bi-check-lg"></i> Confirmer' : 'Refuser' ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <!-- AUJOURD'HUI -->
        <div class="bp-card mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-sun text-warning me-1"></i> Aujourd'hui <span class="text-muted fw-normal fs-6">· <?= e(jour_court(date('Y-m-d'))) ?></span></h5>
                <a href="agenda.php" class="btn btn-soft btn-sm">Agenda</a>
            </div>

            <?php if (!$aujourdhui): ?>
                <div class="empty-state py-3"><i class="bi bi-cup-hot"></i>Aucun rendez-vous confirmé aujourd'hui.</div>
            <?php endif; ?>

            <div class="timeline">
                <?php foreach ($aujourdhui as $r): ?>
                    <?php
                    $debut = strtotime(date('Y-m-d') . ' ' . $r['heure_debut']);
                    $fin = strtotime(date('Y-m-d') . ' ' . $r['heure_fin']);
                    $etat = time() > $fin ? 'fait' : (time() >= $debut ? 'en-cours' : 'a-venir');
                    ?>
                    <a href="client.php?id=<?= (int) $r['client_id'] ?>" class="timeline-item <?= $etat ?>">
                        <span class="timeline-heure"><?= heure($r['heure_debut']) ?></span>
                        <span class="timeline-point"></span>
                        <span class="flex-grow-1">
                            <strong><?= e($r['client']) ?></strong> <?= $etat === 'en-cours' ? '<span class="badge bg-primary ms-1">En cours</span>' : '' ?>
                            <span class="d-block small text-muted"><?= e($r['nom_service']) ?> · jusqu'à <?= heure($r['heure_fin']) ?><?= $r['telephone'] ? ' · ' . e($r['telephone']) : '' ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- PROCHAINS JOURS -->
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
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr><th>Date</th><th>Créneau</th><th>Service</th><th>Client</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prochaines as $r): ?>
                        <tr>
                            <td class="fw-semibold text-capitalize-first"><?= e(jour_court($r['date_disponible'])) ?></td>
                            <td><?= heure($r['heure_debut']) ?> - <?= heure($r['heure_fin']) ?></td>
                            <td><?= e($r['nom_service']) ?> <small class="text-muted">· <?= prix($r['prix']) ?></small></td>
                            <td><a href="client.php?id=<?= (int) $r['client_id'] ?>" class="text-decoration-none"><?= e($r['client']) ?></a></td>
                            <td><?= statut_badge($r['statut']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- ======================================================
    COLONNE LATÉRALE
    ====================================================== -->
    <div class="col-xl-4">

        <!-- TENDANCE -->
        <div class="bp-card mb-4 viz-root">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h5 class="mb-0">Chiffre d'affaires</h5>
                <a href="stats.php" class="small">Statistiques →</a>
            </div>
            <p class="small text-muted mb-4">8 dernières semaines · barre claire : semaine en cours.</p>
            <?= chart_colonnes($semaines, fn($v) => prix($v), "Chiffre d'affaires des 8 dernières semaines", '€', 4) ?>
        </div>

        <!-- RACCOURCIS -->
        <div class="bp-card mb-4">
            <h5 class="mb-3">Raccourcis</h5>
            <div class="row g-2">
                <?php foreach ([
                    ['horaires.php', 'bi-calendar-plus', 'Ouvrir des créneaux'],
                    ['conges.php', 'bi-airplane', 'Poser des congés'],
                    ['partager.php', 'bi-qr-code', 'Lien & QR code'],
                    ['services.php', 'bi-tags', 'Mes tarifs'],
                ] as [$lien, $icone, $libelle]): ?>
                    <div class="col-6">
                        <a href="<?= $lien ?>" class="raccourci"><i class="bi <?= $icone ?>"></i><?= $libelle ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- DERNIERS AVIS -->
        <div class="bp-card">
            <h5 class="mb-3">Derniers avis <?php if ($noteMoyenne): ?><span class="text-muted fw-normal fs-6">· <?= e($noteMoyenne) ?>/5</span><?php endif; ?></h5>
            <?php if (!$derniersAvis): ?>
                <p class="small text-muted mb-0">Vos premiers avis apparaîtront ici après vos rendez-vous.</p>
            <?php endif; ?>
            <?php foreach ($derniersAvis as $a): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between"><strong class="small"><?= e(explode(' ', $a['client'])[0]) ?></strong><?= etoiles($a['note']) ?></div>
                    <p class="small text-muted mb-0">« <?= e(mb_strimwidth($a['commentaire'], 0, 110, '…')) ?> »</p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>/assets/charts.js" defer></script>

<?php include("../includes/dash_footer.php"); ?>
