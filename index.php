<?php
require_once("includes/auth.php");

// ======================================================
// UTILISATEUR CONNECTÉ ?
// ======================================================
$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <!-- ======================================================
    META
    ====================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BookPro - Réservez un professionnel</title>

    <!-- ======================================================
    BOOTSTRAP
    ====================================================== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ======================================================
    BOOTSTRAP ICONS
    ====================================================== -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- ======================================================
    CSS
    ====================================================== -->
    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

    <!-- ======================================================
NAVBAR
====================================================== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">

    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand fw-bold" href="index.php">
            BookPro
        </a>

        <!-- MENU -->
        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- ACCUEIL -->
            <a class="nav-link text-white" href="index.php">
                Accueil
            </a>

            <!-- SERVICES -->
            <a class="nav-link text-white" href="user/services.php">
                Services
            </a>

            <?php if ($user): ?>

                <!-- MON COMPTE -->
                <a class="nav-link text-white"
                   href="<?= ltrim(dashboard_path($user["role"] ?? ""), "/") ?>">

                    <i class="bi bi-person-circle"></i>

                </a>

                <!-- DÉCONNEXION -->
                <a class="nav-link text-danger"
                   href="auth/logout.php">

                    <i class="bi bi-box-arrow-right"></i>

                </a>

            <?php else: ?>

                <!-- PROFESSIONNELS -->
                <a class="nav-link text-white" href="auth/pro-register.php">
                    Devenir Professionnel
                </a>

                <!-- CONNEXION -->
                <a class="login-btn"
                   href="auth/login.php">

                    Se connecter

                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

     <!--====================================================== 
    HERO ====================================================== --> 
    <section class="hero">
        <div class="text-center">
            <h1>BookPro</h1>
            <p> Trouvez et réservez vos services en quelques clics </p> 
            <!-- SEARCH -->
            <form class="search-box" method="GET" action="user/services.php">
                <input type="text" name="search" class="form-control form-control-lg mb-3" placeholder="Plombier, mécanicien, menuisier...">
                <button class="btn btn-light w-100"> Rechercher </button> 
            </form>
        </div>
    </section>

    <!-- ======================================================
CATÉGORIES POPULAIRES
====================================================== -->
    <section class="py-5 bg-light">

        <div class="container">

            <h2 class="text-center mb-5">
                Services populaires
            </h2>

            <div class="row g-4">

                <?php
                // Catégorie => [icône, mot-clé de recherche (métier du pro)]
                $categories = [
                    'Plomberie'   => ['bi-droplet', 'Plomb'],
                    'Mécanique'   => ['bi-car-front', 'Mécani'],
                    'Électricité' => ['bi-lightning', 'Électri'],
                    'Menuiserie'  => ['bi-hammer', 'Menuis'],
                ];
                ?>

                <?php foreach ($categories as $nom => [$icon, $recherche]): ?>
                <div class="col-md-3">
                    <a href="user/services.php?search=<?= urlencode($recherche) ?>" class="text-decoration-none text-dark">
                        <div class="card text-center p-4 h-100">
                            <i class="bi <?= $icon ?> fs-1 text-primary"></i>
                            <h5 class="mt-3"><?= $nom ?></h5>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>

            </div>

        </div>

    </section>

    <!-- ======================================================
COMMENT ÇA MARCHE
====================================================== -->
    <section class="py-5">

        <div class="container">

            <h2 class="text-center mb-5">
                Comment ça fonctionne ?
            </h2>

            <div class="row text-center">

                <div class="col-md-4">

                    <i class="bi bi-search fs-1 text-primary"></i>

                    <h4 class="mt-3">
                        Recherchez
                    </h4>

                    <p>
                        Trouvez le service dont vous avez besoin.
                    </p>

                </div>

                <div class="col-md-4">

                    <i class="bi bi-calendar-check fs-1 text-primary"></i>

                    <h4 class="mt-3">
                        Réservez
                    </h4>

                    <p>
                        Choisissez une date disponible.
                    </p>

                </div>

                <div class="col-md-4">

                    <i class="bi bi-star fs-1 text-primary"></i>

                    <h4 class="mt-3">
                        Profitez
                    </h4>

                    <p>
                        Faites intervenir un professionnel qualifié.
                    </p>

                </div>

            </div>

        </div>

    </section>

    <!-- ======================================================
POURQUOI BOOKPRO
====================================================== -->
    <section class="py-5 bg-light">

        <div class="container">

            <h2 class="text-center mb-5">
                Pourquoi choisir BookPro ?
            </h2>

            <div class="row">

                <div class="col-md-4">

                    <div class="card p-4 h-100">

                        <h4>
                            Professionnels vérifiés
                        </h4>

                        <p>
                            Tous nos partenaires sont contrôlés.
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card p-4 h-100">

                        <h4>
                            Réservation rapide
                        </h4>

                        <p>
                            Quelques clics suffisent.
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card p-4 h-100">

                        <h4>
                            Avis clients
                        </h4>

                        <p>
                            Consultez les retours d'expérience.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- ======================================================
FOOTER
====================================================== -->
    <footer class="bg-dark text-white py-4">

        <div class="container text-center">

            <p class="mb-0">

                © 2026 BookPro - Tous droits réservés

            </p>

        </div>

    </footer>

</body>

</html>
