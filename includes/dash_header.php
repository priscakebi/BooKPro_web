<?php
// ======================================================
// MISE EN PAGE DES ESPACES CONNECTÉS (client, pro, admin)
// Variables attendues : $pageTitle, $active (clé du menu)
// ======================================================

require_once __DIR__ . "/auth.php";

$menus = [
    'client' => [
        'dashboard'    => ['/user/dashboard.php', 'bi-speedometer2', 'Tableau de bord'],
        'services'     => ['/user/services.php', 'bi-search', 'Trouver un service'],
        'reservations' => ['/user/reservations.php', 'bi-calendar-event', 'Mes réservations'],
    ],
    'professionnel' => [
        'dashboard'    => ['/pro/pro-dashboard.php', 'bi-speedometer2', 'Tableau de bord'],
        'services'     => ['/pro/services.php', 'bi-briefcase', 'Mes services'],
        'horaires'     => ['/pro/horaires.php', 'bi-clock', 'Disponibilités'],
        'reservations' => ['/pro/rdv.php', 'bi-calendar-event', 'Réservations'],
    ],
    'admin' => [
        'dashboard'    => ['/admin/dashboard.php', 'bi-speedometer2', 'Tableau de bord'],
        'users'        => ['/admin/users.php', 'bi-people', 'Utilisateurs'],
    ],
];

$menu = $menus[$_SESSION['role'] ?? ''] ?? [];
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle ?? 'Mon espace') ?> - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/dashboard.css">

</head>

<body class="dashboard-page">

<div class="d-flex">

    <!-- ======================================================
    SIDEBAR
    ====================================================== -->
    <div class="sidebar p-3">

        <a href="<?= BASE_URL ?>/index.php" class="text-white text-decoration-none">
            <h3 class="mb-4">BookPro</h3>
        </a>

        <!-- USER INFO -->
        <div class="user-box mb-4">

            <i class="bi bi-person-circle"></i>

            <div>
                <small>Bienvenue</small><br>
                <strong><?= e($_SESSION['user']['nom'] ?? '') ?></strong>
            </div>

        </div>

        <!-- MENU -->
        <?php foreach ($menu as $key => [$href, $icon, $label]): ?>
            <a href="<?= BASE_URL . $href ?>" class="menu <?= $key === $active ? 'active' : '' ?>">
                <i class="bi <?= $icon ?>"></i> <?= e($label) ?>
            </a>
        <?php endforeach; ?>

        <a href="<?= BASE_URL ?>/auth/logout.php" class="menu text-danger">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>

    </div>

    <!-- ======================================================
    CONTENT
    ====================================================== -->
    <div class="content p-4 w-100">

        <h2 class="mb-4"><?= e($pageTitle ?? '') ?></h2>

        <?php include __DIR__ . "/alertes.php"; ?>
