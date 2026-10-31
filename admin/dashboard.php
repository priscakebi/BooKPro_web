<?php

// ======================================================
// TABLEAU DE BORD ADMINISTRATEUR
// ======================================================

require_once("../includes/auth.php");

require_login('admin');

require("../config/database.php");
require("../includes/charts.php");

// Requête préparée en une ligne
$q = function ($sql, $params = []) use ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
};

// ======================================================
// CHIFFRES CLÉS (30 derniers jours vs 30 précédents)
// ======================================================

$roles = $q("SELECT role, COUNT(*) FROM users WHERE suspendu_at IS NULL GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$suspendus = (int) $q("SELECT COUNT(*) FROM users WHERE suspendu_at IS NOT NULL")->fetchColumn();

function periode(callable $q, $debut, $fin)
{
    $inscrits = (int) $q("SELECT COUNT(*) FROM users WHERE role <> 'admin' AND created_at >= ? AND created_at < ?", [$debut, $fin])->fetchColumn();

    $r = $q("
        SELECT COUNT(*) AS demandes,
               COALESCE(SUM(r.statut = 'confirmee'), 0) AS confirmes,
               COALESCE(SUM(CASE WHEN r.statut = 'confirmee' THEN s.prix END), 0) AS volume
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        WHERE r.created_at >= ? AND r.created_at < ?
    ", [$debut, $fin])->fetch();

    return ['inscrits' => $inscrits] + $r;
}

$maintenant = date('Y-m-d H:i:s', strtotime('+1 second'));
$il30 = date('Y-m-d H:i:s', strtotime('-30 days'));
$il60 = date('Y-m-d H:i:s', strtotime('-60 days'));

$k = periode($q, $il30, $maintenant);
$kPrec = periode($q, $il60, $il30);

function evolution_admin($actuel, $precedent)
{
    if (!$precedent) {
        return '<span class="delta flat">— pas de comparaison</span>';
    }

    $ecart = ($actuel - $precedent) / $precedent * 100;

    if (abs($ecart) < 0.5) {
        return '<span class="delta flat">= stable</span>';
    }

    return '<span class="delta ' . ($ecart > 0 ? 'up' : 'down') . '"><i class="bi ' . ($ecart > 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right') . '"></i> '
        . ($ecart > 0 ? '+' : '') . number_format($ecart, 0, ',', ' ') . ' %</span>';
}

$noteGlobale = $q("SELECT ROUND(AVG(note), 1) FROM avis")->fetchColumn();
$nbAvis = (int) $q("SELECT COUNT(*) FROM avis")->fetchColumn();

// ======================================================
// ÉVOLUTION SUR 12 MOIS
// ======================================================

$moisCourts = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$debut12 = date('Y-m-01', strtotime('-11 months'));

$inscritsMois = $q("SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM users WHERE role <> 'admin' AND created_at >= ? GROUP BY 1", [$debut12])->fetchAll(PDO::FETCH_KEY_PAIR);
$rdvMois = $q("
    SELECT DATE_FORMAT(d.date_disponible, '%Y-%m'), COUNT(*) FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    WHERE r.statut = 'confirmee' AND d.date_disponible BETWEEN ? AND CURDATE()
    GROUP BY 1
", [$debut12])->fetchAll(PDO::FETCH_KEY_PAIR);

$serie = function ($donnees) use ($debut12, $moisCourts) {
    $points = [];
    for ($t = strtotime($debut12); $t <= time(); $t = strtotime('+1 month', $t)) {
        $m = date('Y-m', $t);
        $points[] = [
            'label' => $moisCourts[date('n', $t)],
            'tip' => $moisCourts[date('n', $t)] . ' ' . date('Y', $t) . ($m === date('Y-m') ? ' (en cours)' : ''),
            'valeur' => (int) ($donnees[$m] ?? 0),
            'attenue' => $m === date('Y-m'),
        ];
    }
    return $points;
};

// ======================================================
// PROS : les plus actifs, et ceux à accompagner
// ======================================================

$topPros = array_map(fn($p) => [
    'label' => $p['nom'],
    'valeur' => (float) $p['volume'],
    'detail' => $p['rdv'] . ' RDV confirmés',
], $q("
    SELECT COALESCE(u.entreprise, u.nom) AS nom, COUNT(*) AS rdv, SUM(s.prix) AS volume
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.statut = 'confirmee' AND d.date_disponible >= CURDATE() - INTERVAL 30 DAY AND d.date_disponible <= CURDATE()
    GROUP BY u.id ORDER BY volume DESC LIMIT 5
")->fetchAll());

// Profils incomplets : invisibles ou non réservables par les clients
$incomplets = $q("
    SELECT u.id, u.nom, u.entreprise, u.email, u.created_at,
           (SELECT COUNT(*) FROM services s WHERE s.user_id = u.id) AS nb_services,
           (SELECT COUNT(*) FROM disponibilites d JOIN services s ON d.service_id = s.id
             WHERE s.user_id = u.id AND d.date_disponible >= CURDATE()) AS nb_creneaux
    FROM users u
    WHERE u.role = 'professionnel' AND u.suspendu_at IS NULL
    HAVING nb_services = 0 OR nb_creneaux = 0
    ORDER BY u.created_at DESC LIMIT 6
")->fetchAll();

$derniersInscrits = $q("SELECT id, nom, email, role, created_at FROM users WHERE role <> 'admin' ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Avis négatifs récents (à vérifier)
$avisNegatifs = $q("
    SELECT a.id, a.note, a.commentaire, a.created_at, c.nom AS client, COALESCE(p.entreprise, p.nom) AS pro
    FROM avis a
    JOIN users c ON a.user_id = c.id
    JOIN reservations r ON a.reservation_id = r.id
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users p ON s.user_id = p.id
    WHERE a.note <= 2
    ORDER BY a.created_at DESC LIMIT 4
")->fetchAll();

$pageTitle = "Administration";
$pageSubtitle = "Vue d'ensemble de la plateforme · 30 derniers jours comparés aux 30 précédents.";
$active = "dashboard";
include("../includes/dash_header.php");

?>

<div class="viz-root">

<!-- ======================================================
CHIFFRES CLÉS
====================================================== -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-3">
        <a href="users.php" class="bp-card card-hover h-100 d-block text-decoration-none text-dark">
            <div class="stat-label mb-1">Utilisateurs actifs</div>
            <div class="stat-value fs-2 fw-bold"><?= (int) (($roles['client'] ?? 0) + ($roles['professionnel'] ?? 0)) ?></div>
            <div class="small text-muted"><?= (int) ($roles['client'] ?? 0) ?> clients · <?= (int) ($roles['professionnel'] ?? 0) ?> pros<?= $suspendus ? ' · ' . $suspendus . ' suspendu' . ($suspendus > 1 ? 's' : '') : '' ?></div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Nouveaux inscrits</div>
            <div class="stat-value fs-2 fw-bold"><?= (int) $k['inscrits'] ?></div>
            <?= evolution_admin($k['inscrits'], $kPrec['inscrits']) ?>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Réservations demandées</div>
            <div class="stat-value fs-2 fw-bold"><?= (int) $k['demandes'] ?></div>
            <?= evolution_admin($k['demandes'], $kPrec['demandes']) ?>
            <span class="small text-muted">· <?= $k['demandes'] ? round($k['confirmes'] / $k['demandes'] * 100) : 0 ?> % confirmées</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="bp-card h-100">
            <div class="stat-label mb-1">Volume d'affaires confirmé</div>
            <div class="stat-value fs-2 fw-bold"><?= prix($k['volume']) ?></div>
            <?= evolution_admin($k['volume'], $kPrec['volume']) ?>
            <?php if ($noteGlobale): ?><div class="small text-muted mt-1"><?= etoiles($noteGlobale) ?> <?= e($noteGlobale) ?>/5 · <?= $nbAvis ?> avis</div><?php endif; ?>
        </div>
    </div>

</div>

<!-- ======================================================
ÉVOLUTION
====================================================== -->
<div class="row g-4 mb-4">

    <div class="col-xl-6">
        <div class="bp-card h-100">
            <h5 class="mb-1">Nouveaux inscrits par mois</h5>
            <p class="small text-muted mb-4">Clients et professionnels · barre claire : mois en cours.</p>
            <?= chart_colonnes($serie($inscritsMois), fn($v) => $v . ' inscrit' . ($v > 1 ? 's' : ''), "Nouveaux inscrits par mois") ?>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="bp-card h-100">
            <h5 class="mb-1">Rendez-vous confirmés par mois</h5>
            <p class="small text-muted mb-4">Toute la plateforme · barre claire : mois en cours.</p>
            <?= chart_colonnes($serie($rdvMois), fn($v) => $v . ' RDV', "Rendez-vous confirmés par mois") ?>
        </div>
    </div>

</div>

<div class="row g-4">

    <!-- ======================================================
    PROS
    ====================================================== -->
    <div class="col-xl-6">

        <div class="bp-card mb-4">
            <h5 class="mb-1">Pros les plus actifs</h5>
            <p class="small text-muted mb-3">Volume d'affaires confirmé sur 30 jours.</p>
            <?php if ($topPros): ?>
                <?= chart_barres($topPros, fn($v) => prix($v), "Pros les plus actifs (30 jours)") ?>
            <?php else: ?>
                <p class="small text-muted mb-0">Aucun rendez-vous sur la période.</p>
            <?php endif; ?>
        </div>

        <div class="bp-card">
            <h5 class="mb-1"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Profils pros à accompagner</h5>
            <p class="small text-muted mb-3">Sans service ou sans créneau à venir : les clients ne peuvent pas les réserver.</p>
            <?php if (!$incomplets): ?>
                <p class="small text-muted mb-0">Tous les pros sont réservables. 🎉</p>
            <?php endif; ?>
            <?php foreach ($incomplets as $p): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-top gap-2">
                    <div class="small min-w-0">
                        <strong><?= e($p['entreprise'] ?: $p['nom']) ?></strong>
                        <span class="text-muted d-block text-truncate"><?= e($p['email']) ?></span>
                    </div>
                    <span class="tag bg-light text-dark text-nowrap"><?= !$p['nb_services'] ? 'Aucun service' : 'Aucun créneau' ?></span>
                    <a href="mailto:<?= e($p['email']) ?>?subject=<?= rawurlencode('Complétez votre profil BookPro') ?>" class="btn btn-soft btn-sm" title="Écrire"><i class="bi bi-envelope"></i></a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ======================================================
    ACTIVITÉ RÉCENTE
    ====================================================== -->
    <div class="col-xl-6">

        <div class="bp-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Derniers inscrits</h5>
                <a href="users.php" class="btn btn-soft btn-sm">Tous les comptes</a>
            </div>
            <?php foreach ($derniersInscrits as $u): ?>
                <div class="d-flex align-items-center gap-3 py-2 border-top">
                    <?= avatar($u, 36) ?>
                    <div class="flex-grow-1 small min-w-0">
                        <strong><?= e($u['nom']) ?></strong>
                        <span class="text-muted d-block text-truncate"><?= e($u['email']) ?></span>
                    </div>
                    <span class="tag <?= $u['role'] === 'professionnel' ? '' : 'bg-light text-dark' ?>"><?= $u['role'] === 'professionnel' ? 'Pro' : 'Client' ?></span>
                    <small class="text-muted text-nowrap"><?= date_fr($u['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bp-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Avis négatifs récents</h5>
                <a href="avis.php?note=bas" class="btn btn-soft btn-sm">Modérer</a>
            </div>
            <?php if (!$avisNegatifs): ?>
                <p class="small text-muted mb-0">Aucun avis de 1 ou 2 étoiles.</p>
            <?php endif; ?>
            <?php foreach ($avisNegatifs as $a): ?>
                <div class="py-2 border-top small">
                    <div class="d-flex justify-content-between"><span><strong><?= e($a['client']) ?></strong> → <?= e($a['pro']) ?></span><?= etoiles($a['note']) ?></div>
                    <p class="text-muted mb-0">« <?= e(mb_strimwidth($a['commentaire'], 0, 140, '…')) ?> »</p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

</div>

<script src="<?= BASE_URL ?>/assets/charts.js" defer></script>

<?php include("../includes/dash_footer.php"); ?>
