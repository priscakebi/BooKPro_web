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

    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/icons/favicon.png">

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

            <?php
            // Liens du menu selon qui est connecté
            $liensNav = [
                ''              => [['/user/services.php', 'Trouver un pro'], ['/auth/pro-login.php', 'Espace pro']],
                'client'        => [['/user/services.php', 'Trouver un pro'], ['/user/reservations.php', 'Mes réservations']],
                'professionnel' => [['/pro/agenda.php', 'Mon agenda'], ['/pro/rdv.php', 'Réservations'], ['/user/pro.php?id=' . (int) ($_SESSION['user_id'] ?? 0), 'Ma page publique']],
                'admin'         => [['/admin/dashboard.php', 'Administration'], ['/user/services.php', 'Voir les pros']],
            ][$connecte ? $role : ''] ?? [];
            ?>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

                <?php foreach ($liensNav as [$href, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL . $href ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>

                <?php if ($connecte): ?>

                    <!-- Compte : avatar + menu -->
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= avatar($_SESSION['user'] ?? [], 32) ?>
                            <span><?= e(explode(' ', $_SESSION['user']['nom'] ?? '')[0]) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
                            <li><a class="dropdown-item rounded-3" href="<?= BASE_URL . dashboard_path($role) ?>"><i class="bi bi-grid-1x2 me-2"></i>Mon espace</a></li>
                            <?php if ($role === 'professionnel'): ?>
                                <li><a class="dropdown-item rounded-3" href="<?= BASE_URL ?>/pro/profil.php"><i class="bi bi-person-badge me-2"></i>Mon profil</a></li>
                            <?php elseif ($role === 'client'): ?>
                                <li><a class="dropdown-item rounded-3" href="<?= BASE_URL ?>/user/reservations.php"><i class="bi bi-calendar-event me-2"></i>Mes réservations</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item rounded-3 text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>

                <?php else: ?>

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
