<?php
// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================

require("../config/database.php");

// ======================================================
// RÉCUPÉRATION DES SERVICES + NOM DU PROFESSIONNEL
// ======================================================

$stmt = $pdo->query("
    SELECT
        services.*,
        users.nom
    FROM services
    INNER JOIN users
        ON services.user_id = users.id
    ORDER BY services.id DESC
");

$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Services - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/services.css">

</head>

<body>

<!-- ======================================================
NAVBAR
====================================================== -->

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">

    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand fw-bold" href="../index.php">
            BookPro
        </a>

        <!-- Bouton responsive -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarBookPro">

            <span class="navbar-toggler-icon"></span>

        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarBookPro">

            <!-- Barre de recherche -->
            <form class="d-flex mx-auto" method="GET" action="services.php">

                <input
                    class="form-control me-2"
                    type="search"
                    name="search"
                    placeholder="Rechercher un service...">

                <button class="btn btn-light" type="submit">

                    <i class="bi bi-search"></i>

                </button>

            </form>

            <!-- Liens -->
            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Accueil -->
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-house-door-fill"></i>
                        Accueil
                    </a>
                </li>

                <!-- Mon compte -->
                <li class="nav-item">
                    <a class="nav-link" href="../user/dashboard.php">
                        <i class="bi bi-person-circle"></i>
                        Mon compte
                    </a>
                </li>

                <!-- Déconnexion -->
                <li class="nav-item">
                    <a class="nav-link text-warning" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        Déconnexion
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>

<!-- ======================================================
TITRE
====================================================== -->

<section class="py-5 bg-light">

    <div class="container text-center">

        <h1 class="fw-bold">
            Nos services
        </h1>

        <p class="text-muted">
            Découvrez les prestations proposées par nos professionnels.
        </p>

    </div>

</section>

<!-- ======================================================
SERVICES
====================================================== -->

<section class="container py-5">

    <?php if (empty($services)): ?>

        <div class="alert alert-info text-center">

            <i class="bi bi-info-circle-fill"></i>

            Aucun service n'a encore été ajouté.

        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($services as $service): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <!-- Nom du service -->
                            <h4 class="fw-bold mb-3">
                                <?= htmlspecialchars($service["nom_service"]) ?>
                            </h4>

                            <!-- Professionnel -->
                            <p>
                                <i class="bi bi-person-fill text-primary"></i>
                                <strong>Professionnel :</strong>
                                <?= htmlspecialchars($service["nom"]) ?>
                            </p>

                            <!-- Durée -->
                            <p>
                                <i class="bi bi-clock-fill text-primary"></i>
                                <strong>Durée :</strong>
                                <?= htmlspecialchars($service["duree"]) ?> min
                            </p>

                            <!-- Prix -->
                            <p>
                                <i class="bi bi-cash text-primary"></i>
                                <strong>Prix :</strong>
                                <?= htmlspecialchars($service["prix"]) ?> €
                            </p>

                            <!-- Bouton -->
                            <button class="btn btn-primary w-100">
                                Réserver
                            </button>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</body>
</html>