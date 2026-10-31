<?php
// ======================================================
// MISE EN PAGE DES ESPACES CONNECTÉS (client, pro, admin)
// Variables attendues : $pageTitle, $active (clé du menu)
// Optionnelles : $pageSubtitle, $topAction (HTML à droite du titre)
// ======================================================

require_once __DIR__ . "/auth.php";

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

// Pastilles : demandes à traiter (pro), avis récents à modérer (admin)
$aTraiter = 0;

if ($role === 'professionnel' && isset($pdo)) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        WHERE s.user_id = ? AND r.statut = 'en_attente' AND d.date_disponible >= CURDATE()
    ");
    $stmt->execute([$userId]);
    $aTraiter = (int) $stmt->fetchColumn();
}

// ======================================================
// MENUS PAR RÔLE, REGROUPÉS EN RUBRIQUES
// clé => [lien, icône, libellé, pastille]
// ======================================================
$menus = [
    'client' => [
        '' => [
            'dashboard'    => ['/user/dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
            'services'     => ['/user/services.php', 'bi-search', 'Trouver un pro'],
            'reservations' => ['/user/reservations.php', 'bi-calendar-event', 'Mes réservations'],
        ],
    ],
    'professionnel' => [
        'Activité' => [
            'dashboard'    => ['/pro/pro-dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
            'agenda'       => ['/pro/agenda.php', 'bi-calendar-week', 'Agenda'],
            'reservations' => ['/pro/rdv.php', 'bi-calendar-check', 'Réservations', $aTraiter],
            'clients'      => ['/pro/clients.php', 'bi-people', 'Clients'],
            'stats'        => ['/pro/stats.php', 'bi-graph-up-arrow', 'Statistiques'],
        ],
        'Ma vitrine' => [
            'profil'       => ['/pro/profil.php', 'bi-person-badge', 'Mon profil'],
            'services'     => ['/pro/services.php', 'bi-tags', 'Services & tarifs'],
            'partager'     => ['/pro/partager.php', 'bi-qr-code', 'Partager ma page'],
            'page'         => ['/user/pro.php?id=' . $userId, 'bi-box-arrow-up-right', 'Voir ma page publique'],
        ],
        'Organisation' => [
            'ouverture'    => ['/pro/ouverture.php', 'bi-shop', "Horaires d'ouverture"],
            'horaires'     => ['/pro/horaires.php', 'bi-calendar-plus', 'Disponibilités'],
            'conges'       => ['/pro/conges.php', 'bi-airplane', 'Congés & fermetures'],
        ],
    ],
    'admin' => [
        '' => [
            'dashboard'    => ['/admin/dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
            'users'        => ['/admin/users.php', 'bi-people', 'Utilisateurs'],
            'avis'         => ['/admin/avis.php', 'bi-chat-square-quote', 'Avis'],
        ],
    ],
];

// Barre d'onglets en bas de l'écran sur mobile (les essentiels)
$onglets = [
    'client'        => ['dashboard' => ['/user/dashboard.php', 'bi-grid-1x2', 'Accueil'], 'services' => ['/user/services.php', 'bi-search', 'Rechercher'], 'reservations' => ['/user/reservations.php', 'bi-calendar-event', 'Mes RDV']],
    'professionnel' => ['dashboard' => ['/pro/pro-dashboard.php', 'bi-grid-1x2', 'Accueil'], 'agenda' => ['/pro/agenda.php', 'bi-calendar-week', 'Agenda'], 'reservations' => ['/pro/rdv.php', 'bi-calendar-check', 'RDV', $aTraiter], 'clients' => ['/pro/clients.php', 'bi-people', 'Clients']],
    'admin'         => ['dashboard' => ['/admin/dashboard.php', 'bi-grid-1x2', 'Accueil'], 'users' => ['/admin/users.php', 'bi-people', 'Comptes'], 'avis' => ['/admin/avis.php', 'bi-chat-square-quote', 'Avis']],
][$role] ?? [];

$sections = $menus[$role] ?? [];
$active = $active ?? '';
$libellesRole = ['client' => 'Client', 'professionnel' => 'Professionnel', 'admin' => 'Administrateur'];

// Application installable : pro et client ont chacun la leur
$app = ['professionnel' => 'pro', 'client' => 'user'][$role] ?? null;
if ($app) {
    require_once __DIR__ . "/webpush.php";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <title><?= e($pageTitle ?? 'Mon espace') ?> - BookPro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/app.css">

    <!-- Active les animations uniquement si le JavaScript fonctionne -->
    <script>document.documentElement.classList.add("js");</script>

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/icons/favicon.png">

    <?php if ($app): ?>
        <!-- Application installable (PWA) -->
        <link rel="manifest" href="<?= BASE_URL ?>/<?= $app ?>/manifest.json">
        <meta name="theme-color" content="#16123a">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="<?= $app === 'pro' ? 'BookPro Pro' : 'BookPro' ?>">
        <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/icons/apple-touch-icon.png">
        <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php endif; ?>

</head>

<body data-base="<?= BASE_URL ?>" <?= $app ? 'data-app="' . $app . '" data-vapid="' . e(vapid_public_key()) . '"' : '' ?>>

<div class="app-shell">

    <!-- ======================================================
    SIDEBAR
    ====================================================== -->
    <aside class="app-sidebar">

        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <span class="brand-logo"><i class="bi bi-calendar2-check"></i></span> BookPro
        </a>

        <div class="app-user">
            <?= avatar($_SESSION['user'] ?? [], 42) ?>
            <div class="text-truncate">
                <strong class="d-block text-truncate"><?= e($_SESSION['user']['nom'] ?? '') ?></strong>
                <small><?= e($libellesRole[$role] ?? '') ?></small>
            </div>
        </div>

        <nav class="app-menu">

            <a href="<?= BASE_URL ?>/index.php"><i class="bi bi-house"></i> Accueil du site</a>

            <?php foreach ($sections as $titre => $items): ?>

                <?php if ($titre !== ''): ?>
                    <div class="app-menu-title"><?= e($titre) ?></div>
                <?php endif; ?>

                <?php foreach ($items as $key => $item): ?>
                    <?php [$href, $icon, $label] = $item; $count = $item[3] ?? 0; ?>
                    <a href="<?= BASE_URL . $href ?>" class="<?= $key === $active ? 'active' : '' ?>" <?= $key === 'page' ? 'target="_blank"' : '' ?>>
                        <i class="bi <?= $icon ?>"></i> <?= e($label) ?>
                        <?php if ($count > 0): ?><span class="menu-count"><?= $count ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>

            <?php endforeach; ?>

        </nav>

        <a href="<?= BASE_URL ?>/auth/logout.php" class="app-menu logout d-flex align-items-center gap-2 px-3 py-2 text-decoration-none fw-semibold">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>

    </aside>

    <!-- ======================================================
    BARRE D'ONGLETS (mobile)
    ====================================================== -->
    <nav class="app-tabbar" aria-label="Navigation principale">
        <?php foreach ($onglets as $key => $item): ?>
            <?php [$href, $icon, $label] = $item; $count = $item[3] ?? 0; ?>
            <a href="<?= BASE_URL . $href ?>" class="<?= $key === $active ? 'active' : '' ?>">
                <span class="position-relative"><i class="bi <?= $icon ?>"></i><?php if ($count > 0): ?><span class="tab-count"><?= $count ?></span><?php endif; ?></span>
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
        <button type="button" class="app-burger-tab">
            <i class="bi bi-list"></i> Menu
        </button>
    </nav>

    <!-- ======================================================
    CONTENU
    ====================================================== -->
    <main class="app-content">

        <div class="app-topbar">

            <div class="d-flex align-items-center gap-3">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-soft app-home-mobile" aria-label="Accueil du site"><i class="bi bi-house"></i></a>
                <div>
                    <h1><?= e($pageTitle ?? '') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?><p class="text-muted mb-0"><?= e($pageSubtitle) ?></p><?php endif; ?>
                </div>
            </div>

            <?= $topAction ?? '' ?>

        </div>

        <?php include __DIR__ . "/alertes.php"; ?>
