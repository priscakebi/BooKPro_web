<?php
// ======================================================
// MISE EN PAGE CONNEXION / INSCRIPTION (écran partagé)
// Variables : $pageTitle, $sideTitle, $sidePerks (liste), $wide (bool)
// ======================================================

require_once __DIR__ . "/auth.php";

$sideTitle = $sideTitle ?? "Réservez un pro en quelques clics.";
$sidePerks = $sidePerks ?? [
    ['bi-search', 'Comparez', 'Prix, avis et disponibilités en un coup d\'œil.'],
    ['bi-calendar-check', 'Réservez', 'Choisissez un créneau libre, sans appeler.'],
    ['bi-star', 'Notez', 'Partagez votre expérience après l\'intervention.'],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle ?? 'BookPro') ?> - BookPro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/app.css">

    <!-- Active les animations uniquement si le JavaScript fonctionne -->
    <script>document.documentElement.classList.add("js");</script>

</head>

<body>

<div class="auth-wrap">

    <!-- Panneau de gauche (masqué sur mobile) -->
    <aside class="auth-side">

        <a href="<?= BASE_URL ?>/index.php" class="d-flex align-items-center gap-2 fw-bold fs-4">
            <span class="brand-logo"><i class="bi bi-calendar2-check"></i></span> BookPro
        </a>

        <div>
            <h2 class="display-6 fw-bold mb-4"><?= e($sideTitle) ?></h2>

            <ul class="list-unstyled auth-perks">
                <?php foreach ($sidePerks as [$icon, $titre, $texte]): ?>
                    <li>
                        <i class="bi <?= $icon ?>"></i>
                        <div><strong><?= e($titre) ?></strong><br><span class="opacity-75"><?= e($texte) ?></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <small class="opacity-50">© <?= date('Y') ?> BookPro</small>

    </aside>

    <!-- Formulaire -->
    <main class="auth-main">

        <div class="auth-box <?= !empty($wide) ? 'wide' : '' ?>">

            <a href="<?= BASE_URL ?>/index.php" class="d-lg-none d-flex align-items-center gap-2 fw-bold fs-4 text-decoration-none text-dark mb-4">
                <span class="brand-logo"><i class="bi bi-calendar2-check"></i></span> BookPro
            </a>

            <?php include __DIR__ . "/alertes.php"; ?>
