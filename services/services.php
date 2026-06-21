<?php
// ======================================================
// SESSION
// ======================================================
session_start();

// ======================================================
// BDD
// ======================================================
require("../config/database.php");

// ======================================================
// RÉCUPÉRATION DES SERVICES
// ======================================================
$stmt = $pdo->query("
    SELECT 
        services.*,
        users.nom
    FROM services
    INNER JOIN users ON services.user_id = users.id
    ORDER BY services.id DESC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// utilisateur connecté ?
$userConnected = isset($_SESSION["user_id"]);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">

            <!-- SEARCH -->
            <form class="d-flex mx-auto" method="GET" action="services.php">
                <input class="form-control me-2" type="search" name="search" placeholder="Rechercher un service...">
                <button class="btn btn-light">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <!-- MENU -->
            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link" href="../index.php">Accueil</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="services.php">Services</a>
                </li>

                <?php if (isset($_SESSION["user_id"])): ?>

                    <?php
                    if ($_SESSION["role"] == "professionnel") {
                        $dashboard = "../pro/pro-dashboard.php";
                    } elseif ($_SESSION["role"] == "admin") {
                        $dashboard = "../admin/admin-dashboard.php";
                    } else {
                        $dashboard = "../user/dashboard.php";
                    }
                    ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= $dashboard ?>">
                            <i class="bi bi-person-circle"></i> Mon compte
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-warning" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
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
        <h1 class="fw-bold">Nos services</h1>
        <p class="text-muted">Découvrez les prestations proposées par nos professionnels.</p>
    </div>
</section>

<!-- ======================================================
SERVICES LIST
====================================================== -->
<section class="container py-5">

<?php if (empty($services)): ?>

    <div class="alert alert-info text-center">
        Aucun service n'a encore été ajouté.
    </div>

<?php else: ?>

    <div class="row g-4">

        <?php foreach ($services as $service): ?>

            <div class="col-md-6 col-lg-4">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <h4 class="fw-bold">
                            <?= htmlspecialchars($service["nom_service"]) ?>
                        </h4>

                        <p>
                            <?= htmlspecialchars($service["description"]) ?>
                        </p>

                        <p>
                            <i class="bi bi-person"></i>
                            <?= htmlspecialchars($service["nom"]) ?>
                        </p>

                        <p>
                            <i class="bi bi-calendar"></i>
                            <?= date("d/m/Y", strtotime($service["created_at"])) ?>
                        </p>

                        <!-- BOUTON RESERVER -->
                        <a href="../user/prise_rdv.php?service=<?= $service["id"] ?>"
                           class="btn btn-primary w-100">
                            Réserver
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

</section>

</body>
</html>