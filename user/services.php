<?php
// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================

require_once("../includes/auth.php");
require("../config/database.php");

// ======================================================
// RECHERCHE (nom du service, métier ou ville du pro)
// ======================================================

$search = trim($_GET['search'] ?? "");
$like = "%" . $search . "%";

// ======================================================
// RÉCUPÉRATION DES SERVICES + NOM DU PROFESSIONNEL
// ======================================================

$stmt = $pdo->prepare("
    SELECT
        services.*,
        users.nom,
        users.metier,
        users.ville,
        -- Note moyenne et nombre d'avis
        (SELECT ROUND(AVG(a.note), 1) FROM avis a
            JOIN reservations r ON a.reservation_id = r.id
            JOIN disponibilites d ON r.disponibilite_id = d.id
            WHERE d.service_id = services.id) AS note,
        (SELECT COUNT(*) FROM avis a
            JOIN reservations r ON a.reservation_id = r.id
            JOIN disponibilites d ON r.disponibilite_id = d.id
            WHERE d.service_id = services.id) AS nb_avis,
        -- Créneaux encore libres
        (SELECT COUNT(*) FROM disponibilites d
            WHERE d.service_id = services.id
              AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
              AND NOT EXISTS (SELECT 1 FROM reservations r
                              WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)) AS libres
    FROM services
    INNER JOIN users
        ON services.user_id = users.id
    WHERE ? = ''
       OR services.nom_service LIKE ?
       OR services.description LIKE ?
       OR users.metier LIKE ?
       OR users.ville LIKE ?
    ORDER BY libres > 0 DESC, services.id DESC
");

$stmt->execute([$search, $like, $like, $like, $like]);

$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Services - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
                    value="<?= e($search) ?>"
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

                <?php if (isset($_SESSION['user_id'])): ?>

                <!-- Mon compte -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL . dashboard_path($_SESSION['role'] ?? '') ?>">
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

                <?php else: ?>

                <!-- Connexion -->
                <li class="nav-item">
                    <a class="nav-link" href="../auth/login.php">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Se connecter
                    </a>
                </li>

                <?php endif; ?>

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

    <?php include("../includes/alertes.php"); ?>

    <?php if (empty($services)): ?>

        <div class="alert alert-info text-center">

            <i class="bi bi-info-circle-fill"></i>

            <?= $search !== "" ? "Aucun service ne correspond à votre recherche." : "Aucun service n'a encore été ajouté." ?>

        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($services as $service): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body">

                            <!-- Nom du service -->
                            <h4 class="fw-bold mb-1">
                                <?= e($service["nom_service"]) ?>
                            </h4>

                            <!-- Note moyenne -->
                            <p class="mb-3 small">
                                <?php if ($service["nb_avis"] > 0): ?>
                                    <span class="text-warning">★</span>
                                    <?= e($service["note"]) ?>/5 (<?= (int) $service["nb_avis"] ?> avis)
                                <?php else: ?>
                                    <span class="text-muted">Pas encore d'avis</span>
                                <?php endif; ?>
                            </p>

                            <p class="text-muted"><?= e(mb_strimwidth($service["description"], 0, 120, "…")) ?></p>

                            <!-- Professionnel -->
                            <p>
                                <i class="bi bi-person-fill text-primary"></i>
                                <strong>Professionnel :</strong>
                                <?= e($service["nom"]) ?>
                                <?php if ($service["metier"]): ?>(<?= e($service["metier"]) ?>)<?php endif; ?>
                            </p>

                            <?php if ($service["ville"]): ?>
                            <p>
                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                <?= e($service["ville"]) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Durée -->
                            <?php if ($service["duree"]): ?>
                            <p>
                                <i class="bi bi-clock-fill text-primary"></i>
                                <strong>Durée :</strong>
                                <?= (int) $service["duree"] ?> min
                            </p>
                            <?php endif; ?>

                            <!-- Prix -->
                            <p>
                                <i class="bi bi-cash text-primary"></i>
                                <strong>Prix :</strong>
                                <?= e($service["prix"]) ?> €
                            </p>

                            <!-- Bouton -->
                            <?php if ($service["libres"] > 0): ?>
                                <a
                                    href="prendre_rdv.php?service=<?= (int) $service["id"] ?>"
                                    class="btn btn-primary w-100">
                                    Réserver (<?= (int) $service["libres"] ?> créneau<?= $service["libres"] > 1 ? 'x' : '' ?>)
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Aucun créneau disponible
                                </button>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

</body>
</html>