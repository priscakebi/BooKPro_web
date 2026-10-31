<?php

// ======================================================
// STATISTIQUES DU PRO
// Période choisie en haut : tout ce qui est en dessous la suit
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/charts.php");
require("../includes/partage.php");

$proId = $_SESSION['user_id'];

// ======================================================
// PÉRIODE (jours passés, aujourd'hui inclus) + période précédente
// ======================================================

$periodes = [30 => '30 derniers jours', 90 => '3 derniers mois', 365 => '12 derniers mois'];
$n = isset($periodes[(int) ($_GET['periode'] ?? 0)]) ? (int) $_GET['periode'] : 90;

$au = date('Y-m-d');
$du = date('Y-m-d', strtotime("-" . ($n - 1) . " days"));
$auPrec = date('Y-m-d', strtotime("$du -1 day"));
$duPrec = date('Y-m-d', strtotime("$du -$n days"));

// ======================================================
// INDICATEURS D'UNE PÉRIODE
// ======================================================

function indicateurs(PDO $pdo, $proId, $du, $au)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(r.id) AS demandes,
               COALESCE(SUM(r.statut = 'confirmee'), 0) AS honores,
               COALESCE(SUM(CASE WHEN r.statut = 'confirmee' THEN s.prix END), 0) AS ca,
               COALESCE(SUM(r.statut IN ('annulee', 'refusee')), 0) AS perdus
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
    ");
    $stmt->execute([$proId, $du, $au]);
    $i = $stmt->fetch();

    // Remplissage : créneaux publiés qui ont un rendez-vous confirmé
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS creneaux,
               COALESCE(SUM(EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.statut = 'confirmee')), 0) AS occupes
        FROM disponibilites d
        JOIN services s ON d.service_id = s.id
        WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
    ");
    $stmt->execute([$proId, $du, $au]);
    $c = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nb), 0) FROM vues_pro WHERE pro_id = ? AND jour BETWEEN ? AND ?");
    $stmt->execute([$proId, $du, $au]);

    return $i + [
        'remplissage' => $c['creneaux'] ? $c['occupes'] / $c['creneaux'] * 100 : null,
        'creneaux'    => (int) $c['creneaux'],
        'annulation'  => $i['demandes'] ? $i['perdus'] / $i['demandes'] * 100 : null,
        'panier'      => $i['honores'] ? $i['ca'] / $i['honores'] : null,
        'visites'     => (int) $stmt->fetchColumn(),
    ];
}

$k = indicateurs($pdo, $proId, $du, $au);
$kPrec = indicateurs($pdo, $proId, $duPrec, $auPrec);

// Évolution vs période précédente ($points : écart en points de %, pas en %)
function evolution($actuel, $precedent, $hausseBonne = true, $points = false)
{
    if ($actuel === null || $precedent === null || (!$points && $precedent == 0)) {
        return '<span class="delta flat">— pas de comparaison</span>';
    }

    $ecart = $points ? $actuel - $precedent : ($actuel - $precedent) / $precedent * 100;

    if (abs($ecart) < 0.5) {
        return '<span class="delta flat">= stable</span>';
    }

    $bon = ($ecart > 0) === $hausseBonne;
    $fleche = $ecart > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right';

    return '<span class="delta ' . ($bon ? 'up' : 'down') . '"><i class="bi ' . $fleche . '"></i> '
        . ($ecart > 0 ? '+' : '') . number_format($ecart, 0, ',', ' ') . ($points ? ' pt' : ' %') . '</span>';
}

$pct = fn($v) => $v === null ? '—' : number_format($v, 0, ',', ' ') . ' %';

// ======================================================
// CHIFFRE D'AFFAIRES DANS LE TEMPS (jour / semaine / mois)
// ======================================================

$stmt = $pdo->prepare("
    SELECT d.date_disponible, SUM(s.prix) AS ca
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible BETWEEN ? AND ?
    GROUP BY d.date_disponible
");
$stmt->execute([$proId, $du, $au]);
$caParJour = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$moisCourts = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$points = [];

if ($n === 30) {
    $granularite = 'jour';
    for ($t = strtotime($du); $t <= strtotime($au); $t = strtotime('+1 day', $t)) {
        $j = date('Y-m-d', $t);
        $points[$j] = ['label' => date('j', $t), 'tip' => jour_court($j), 'valeur' => 0];
    }
    foreach ($caParJour as $j => $ca) {
        $points[$j]['valeur'] += $ca;
    }
} elseif ($n === 90) {
    $granularite = 'semaine';
    for ($t = strtotime(lundi_de($du)); $t <= strtotime($au); $t = strtotime('+7 days', $t)) {
        $l = date('Y-m-d', $t);
        $points[$l] = ['label' => date('d/m', $t), 'tip' => 'Semaine du ' . jour_court($l), 'valeur' => 0];
    }
    foreach ($caParJour as $j => $ca) {
        $points[lundi_de($j)]['valeur'] += $ca;
    }
    $points[lundi_de($au)]['attenue'] = true; // semaine en cours
} else {
    $granularite = 'mois';
    for ($t = strtotime(date('Y-m-01', strtotime($du))); $t <= strtotime($au); $t = strtotime('+1 month', $t)) {
        $m = date('Y-m', $t);
        $points[$m] = ['label' => $moisCourts[date('n', $t)], 'tip' => $moisCourts[date('n', $t)] . ' ' . date('Y', $t), 'valeur' => 0];
    }
    foreach ($caParJour as $j => $ca) {
        $points[substr($j, 0, 7)]['valeur'] += $ca;
    }
    $points[date('Y-m')]['attenue'] = true; // mois en cours
}

foreach ($points as &$p) {
    if (!empty($p['attenue'])) {
        $p['tip'] .= ' (en cours)';
    }
}
unset($p);

// ======================================================
// JOURS DE LA SEMAINE LES PLUS DEMANDÉS (RDV confirmés)
// ======================================================

$stmt = $pdo->prepare("
    SELECT WEEKDAY(d.date_disponible) + 1 AS jour, COUNT(*) AS nb
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible BETWEEN ? AND ?
    GROUP BY jour
");
$stmt->execute([$proId, $du, $au]);
$parJour = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$joursPoints = [];
foreach (JOURS as $num => $nom) {
    $joursPoints[] = ['label' => mb_substr($nom, 0, 3) . '.', 'tip' => $nom, 'valeur' => (int) ($parJour[$num] ?? 0)];
}

// ======================================================
// SERVICES LES PLUS DEMANDÉS
// ======================================================

$stmt = $pdo->prepare("
    SELECT s.nom_service,
           COUNT(r.id) AS demandes,
           COALESCE(SUM(r.statut = 'confirmee'), 0) AS honores,
           COALESCE(SUM(CASE WHEN r.statut = 'confirmee' THEN s.prix END), 0) AS ca
    FROM services s
    JOIN disponibilites d ON d.service_id = s.id AND d.date_disponible BETWEEN ? AND ?
    JOIN reservations r ON r.disponibilite_id = d.id
    WHERE s.user_id = ?
    GROUP BY s.id
    ORDER BY demandes DESC, ca DESC
    LIMIT 8
");
$stmt->execute([$du, $au, $proId]);
$services = array_map(fn($s) => [
    'label'  => $s['nom_service'],
    'valeur' => (int) $s['demandes'],
    'detail' => $s['honores'] . ' honoré' . ($s['honores'] > 1 ? 's' : '') . ' · ' . prix($s['ca']),
], $stmt->fetchAll());

// ======================================================
// PROVENANCE DES VISITES
// ======================================================

$stmt = $pdo->prepare("SELECT source, SUM(nb) FROM vues_pro WHERE pro_id = ? AND jour BETWEEN ? AND ? GROUP BY source");
$stmt->execute([$proId, $du, $au]);
$vues = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$provenances = [];
foreach (SOURCES_VISITE as $cle => $label) {
    $provenances[] = ['label' => $label, 'valeur' => (int) ($vues[$cle] ?? 0)];
}
usort($provenances, fn($a, $b) => $b['valeur'] <=> $a['valeur']);

// ======================================================
// STATUTS DES DEMANDES (ordre validé : jaune et orange jamais côte à côte)
// ======================================================

$stmt = $pdo->prepare("
    SELECT r.statut, COUNT(*) FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
    GROUP BY r.statut
");
$stmt->execute([$proId, $du, $au]);
$statuts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$segments = [
    ['label' => 'Confirmées', 'valeur' => (int) ($statuts['confirmee'] ?? 0), 'couleur' => '#0ca30c', 'icone' => 'bi-check-circle-fill'],
    ['label' => 'Sans réponse', 'valeur' => (int) ($statuts['en_attente'] ?? 0), 'couleur' => '#fab219', 'icone' => 'bi-hourglass-split', 'texte_sombre' => true],
    ['label' => 'Refusées', 'valeur' => (int) ($statuts['refusee'] ?? 0), 'couleur' => '#d03b3b', 'icone' => 'bi-x-octagon-fill'],
    ['label' => 'Annulées', 'valeur' => (int) ($statuts['annulee'] ?? 0), 'couleur' => '#ec835a', 'icone' => 'bi-slash-circle'],
];

$conversion = $k['visites'] ? $k['demandes'] / $k['visites'] * 100 : null;

$pageTitle = "Statistiques";
$pageSubtitle = "Du " . date_fr($du) . " au " . date_fr($au) . ", comparé aux " . $n . " jours précédents.";
$active = "stats";
include("../includes/dash_header.php");
?>

<div class="viz-root">

<!-- ======================================================
PÉRIODE (un seul filtre, au-dessus de tout)
====================================================== -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach ($periodes as $jours => $label): ?>
        <a href="?periode=<?= $jours ?>" class="chip <?= $jours === $n ? 'active' : '' ?>"><?= $jours === $n ? '<i class="bi bi-check-lg"></i>' : '' ?> <?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($k['demandes'] == 0 && $k['visites'] == 0 && $k['creneaux'] == 0): ?>

    <div class="bp-card empty-state">
        <i class="bi bi-bar-chart"></i>
        <h5 class="text-dark">Pas encore de données sur cette période</h5>
        <p>Vos statistiques apparaîtront dès vos premiers créneaux, visites et réservations.</p>
        <a href="partager.php" class="btn btn-primary">Partager ma page</a>
    </div>

<?php else: ?>

<!-- ======================================================
CHIFFRES CLÉS
====================================================== -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Chiffre d'affaires (RDV confirmés)</div>
            <div class="stat-value fs-2 fw-bold"><?= prix($k['ca']) ?></div>
            <?= evolution($k['ca'], $kPrec['ca']) ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Rendez-vous confirmés</div>
            <div class="stat-value fs-2 fw-bold"><?= (int) $k['honores'] ?></div>
            <?= evolution($k['honores'], $kPrec['honores']) ?>
            <?php if ($k['panier']): ?><div class="small text-muted mt-1">Panier moyen : <?= prix($k['panier']) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Taux de remplissage</div>
            <div class="stat-value fs-2 fw-bold"><?= $pct($k['remplissage']) ?></div>
            <?= chart_jauge($k['remplissage'] ?? 0, "Taux de remplissage") ?>
            <div class="mt-1"><?= evolution($k['remplissage'], $kPrec['remplissage'], true, true) ?> <span class="small text-muted">· <?= $k['creneaux'] ?> créneaux publiés</span></div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Visites → demandes</div>
            <div class="stat-value fs-2 fw-bold"><?= $pct($conversion) ?></div>
            <div class="small text-muted"><?= $k['visites'] ?> visites · <?= (int) $k['demandes'] ?> demandes</div>
            <?php if ($k['annulation'] !== null): ?>
                <div class="small mt-1">Annulations / refus : <strong><?= $pct($k['annulation']) ?></strong> <?= evolution($k['annulation'], $kPrec['annulation'], false, true) ?></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ======================================================
GRAPHIQUES
====================================================== -->
<div class="row g-4">

    <div class="col-xl-8">
        <div class="bp-card h-100">
            <h5 class="mb-1">Chiffre d'affaires par <?= $granularite ?></h5>
            <p class="small text-muted mb-4">Rendez-vous confirmés, au prix du service<?= $granularite !== 'jour' ? ' · barre claire : période en cours' : '' ?>.</p>
            <?= chart_colonnes(array_values($points), fn($v) => prix($v), "Chiffre d'affaires par $granularite", '€') ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="bp-card h-100">
            <h5 class="mb-1">Jours les plus demandés</h5>
            <p class="small text-muted mb-4">Rendez-vous confirmés par jour de la semaine.</p>
            <?= chart_colonnes($joursPoints, fn($v) => $v . ' RDV', "Rendez-vous confirmés par jour de la semaine") ?>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="bp-card h-100">
            <h5 class="mb-1">Services les plus demandés</h5>
            <p class="small text-muted mb-3">Nombre de demandes reçues (survolez pour le détail).</p>
            <?php if ($services): ?>
                <?= chart_barres($services, fn($v) => $v . ' demande' . ($v > 1 ? 's' : ''), "Demandes par service") ?>
            <?php else: ?>
                <div class="empty-state py-3"><i class="bi bi-tags"></i>Aucune demande sur la période.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="bp-card h-100">
            <h5 class="mb-1">D'où viennent vos visiteurs</h5>
            <p class="small text-muted mb-3">Visites de votre page publique. <a href="partager.php">Partagez votre lien et QR code</a> pour en avoir plus.</p>
            <?= chart_barres($provenances, fn($v) => $v . ' visite' . ($v > 1 ? 's' : ''), "Visites par provenance") ?>

            <h6 class="mt-4 mb-2">Ce que deviennent les demandes</h6>
            <?php if ($k['demandes'] > 0): ?>
                <?= chart_segments($segments, "Demandes par statut") ?>
            <?php else: ?>
                <p class="small text-muted mb-0">Aucune demande sur la période.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php endif; ?>

</div>

<script src="<?= BASE_URL ?>/assets/charts.js" defer></script>

<?php include("../includes/dash_footer.php"); ?>
