<?php
// ======================================================
// MISE EN PAGE PUBLIQUE (accueil, recherche, profil pro)
// Variables : $pageTitle, $navTransparent (true sur l'accueil)
// ======================================================

require_once __DIR__ . "/auth.php";

$navTransparent = $navTransparent ?? false;
$connecte = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle ?? 'BookPro') ?> - BookPro</title>

    <meta name="description" content="BookPro : trouvez un professionnel près de chez vous, comparez les prix et réservez en ligne.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/app.css">

    <!-- Active les animations uniquement si le JavaScript fonctionne -->
    <script>document.documentElement.classList.add("js");</script>

</head>

<body>

<!-- ======================================================
NAVBAR
====================================================== -->
<nav class="navbar navbar-expand-lg bp-nav <?= $navTransparent ? 'is-transparent' : '' ?>">

    <div class="container">

        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-logo"><i class="bi bi-calendar2-check"></i></span>
            BookPro
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#bpNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="bpNav">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/user/services.php">Trouver un pro</a>
                </li>

                <?php if ($connecte): ?>

                    <?php if ($role === 'client'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/user/reservations.php">Mes réservations</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="<?= BASE_URL . dashboard_path($role) ?>">
                            <i class="bi bi-person-circle me-1"></i> Mon espace
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php" title="Déconnexion">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/pro-register.php">Vous êtes pro ?</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Connexion</a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="<?= BASE_URL ?>/auth/register.php">S'inscrire</a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>
