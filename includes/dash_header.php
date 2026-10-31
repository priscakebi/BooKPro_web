<?php
// ======================================================
// MISE EN PAGE DES ESPACES CONNECTÉS (client, pro, admin)
// Variables attendues : $pageTitle, $active (clé du menu)
// Optionnelles : $pageSubtitle, $topAction (HTML à droite du titre)
// ======================================================

require_once __DIR__ . "/auth.php";

$role = $_SESSION['role'] ?? '';

// Pastille "à traiter" sur le menu Réservations du pro
$aTraiter = 0;

if ($role === 'professionnel' && isset($pdo)) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        WHERE s.user_id = ? AND r.statut = 'en_attente' AND d.date_disponible >= CURDATE()
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $aTraiter = (int) $stmt->fetchColumn();
}

$menus = [
    'client' => [
        'dashboard'    => ['/user/dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
        'services'     => ['/user/services.php', 'bi-search', 'Trouver un pro'],
        'reservations' => ['/user/reservations.php', 'bi-calendar-event', 'Mes réservations'],
    ],
    'professionnel' => [
        'dashboard'    => ['/pro/pro-dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
        'agenda'       => ['/pro/agenda.php', 'bi-calendar-week', 'Agenda'],
        'profil'       => ['/pro/profil.php', 'bi-person-badge', 'Mon profil'],
        'services'     => ['/pro/services.php', 'bi-tags', 'Services & tarifs'],
        'ouverture'    => ['/pro/ouverture.php', 'bi-shop', "Horaires d'ouverture"],
        'horaires'     => ['/pro/horaires.php', 'bi-calendar-plus', 'Disponibilités'],
        'conges'       => ['/pro/conges.php', 'bi-airplane', 'Congés & fermetures'],
        'reservations' => ['/pro/rdv.php', 'bi-calendar-check', 'Réservations', $aTraiter],
        'clients'      => ['/pro/clients.php', 'bi-people', 'Clients'],
        'stats'        => ['/pro/stats.php', 'bi-graph-up-arrow', 'Statistiques'],
        'partager'     => ['/pro/partager.php', 'bi-qr-code', 'Partager ma page'],
    ],
    'admin' => [
        'dashboard'    => ['/admin/dashboard.php', 'bi-grid-1x2', 'Tableau de bord'],
        'users'        => ['/admin/users.php', 'bi-people', 'Utilisateurs'],
    ],
];

$menu = $menus[$role] ?? [];
$active = $active ?? '';
$libellesRole = ['client' => 'Client', 'professionnel' => 'Professionnel', 'admin' => 'Administrateur'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle ?? 'Mon espace') ?> - BookPro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/app.css">

    <!-- Active les animations uniquement si le JavaScript fonctionne -->
    <script>document.documentElement.classList.add("js");</script>

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/icons/favicon.png">

    <?php if ($role === 'professionnel'): ?>
        <!-- Application pro installable (PWA) -->
        <?php require_once __DIR__ . "/webpush.php"; ?>
        <link rel="manifest" href="<?= BASE_URL ?>/pro/manifest.json">
        <meta name="theme-color" content="#16123a">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="BookPro Pro">
        <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/icons/apple-touch-icon.png">
        <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <?php endif; ?>

</head>

<body data-base="<?= BASE_URL ?>" <?= $role === 'professionnel' ? 'data-vapid="' . e(vapid_public_key()) . '"' : '' ?>>

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

            <?php foreach ($menu as $key => $item): ?>
                <?php [$href, $icon, $label] = $item; $count = $item[3] ?? 0; ?>
                <a href="<?= BASE_URL . $href ?>" class="<?= $key === $active ? 'active' : '' ?>">
                    <i class="bi <?= $icon ?>"></i> <?= e($label) ?>
                    <?php if ($count > 0): ?><span class="menu-count"><?= $count ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if ($role === 'professionnel'): ?>
                <a href="<?= BASE_URL ?>/user/pro.php?id=<?= (int) $_SESSION['user_id'] ?>" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Voir ma page publique
                </a>
            <?php endif; ?>

        </nav>

        <a href="<?= BASE_URL ?>/auth/logout.php" class="app-menu logout d-flex align-items-center gap-2 px-3 py-2 text-decoration-none fw-semibold">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>

    </aside>

    <!-- ======================================================
    CONTENU
    ====================================================== -->
    <main class="app-content">

        <div class="app-topbar">

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-soft app-burger" type="button" aria-label="Menu"><i class="bi bi-list"></i></button>
                <div>
                    <h1><?= e($pageTitle ?? '') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?><p class="text-muted mb-0"><?= e($pageSubtitle) ?></p><?php endif; ?>
                </div>
            </div>

            <?= $topAction ?? '' ?>

        </div>

        <?php include __DIR__ . "/alertes.php"; ?>
