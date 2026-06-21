<?php

// ======================================================
// SESSION
// ======================================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professionnel') {
    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// USER
// ======================================================
$user = [
    "id" => $_SESSION["user_id"],
    "nom" => $_SESSION["nom"],
    "email" => $_SESSION["email"],
    "role" => $_SESSION["role"]
];

// ======================================================
// BDD
// ======================================================
require("../config/database.php");

// ======================================================
// SERVICES PRO
// ======================================================
$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$user['id']]);

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Pro - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/dashboard.css">

</head>

<body class="dashboard-page">

    <div class="d-flex">

        <!-- ======================================================
SIDEBAR PRO
====================================================== -->

        <div class="sidebar p-3">

            <h3 class="text-white mb-4">
                BookPro
            </h3>

            <!-- USER INFO -->
            <div class="user-box mb-4">

                <i class="bi bi-person-circle fs-1 text-white"></i>

                <div class="text-white ms-2">

                    <small>Professionnel</small><br>

                    <strong>
                        <?= htmlspecialchars($user['nom']) ?>
                    </strong>

                </div>

            </div>

            <!-- MENU -->
            <a href="pro-dashboard.php" class="menu active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <a href="../pro/add-services.php" class="menu">
                <i class="bi bi-plus-circle"></i> Ajouter service
            </a>

            <a href="disponibilites.php" class="menu">
                <i class="bi bi-calendar-week"></i>
                Disponibilités
            </a>

            <a href="gestion-rdv.php" class="menu">
                <i class="bi bi-calendar-event"></i> Réservations
            </a>


            <a href="../auth/logout.php" class="menu text-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>

            <div class="text-center mt-3">

                <a href="../index.php" class="text-white">
                    ← Retour à l'accueil
                </a>

            </div>

        </div>

        <!-- ======================================================
CONTENT PRO
====================================================== -->

        <div class="content p-4 w-100">

            <h2 class="mb-4">
                Tableau de bord professionnel
            </h2>

            <!-- ======================================================
    STATS
    ====================================================== -->

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="card stat-card text-center p-4">

                        <i class="bi bi-briefcase fs-1 text-primary"></i>

                        <h4 class="mt-2">
                            <?= count($services) ?>
                        </h4>

                        <p class="text-muted mb-0">
                            Services
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card stat-card text-center p-4">

                        <i class="bi bi-calendar-check fs-1 text-warning"></i>

                        <h4 class="mt-2">0</h4>

                        <p class="text-muted mb-0">
                            Réservations
                        </p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card stat-card text-center p-4">

                        <i class="bi bi-cash-stack fs-1 text-success"></i>

                        <h4 class="mt-2">0 €</h4>

                        <p class="text-muted mb-0">
                            Revenus
                        </p>

                    </div>

                </div>

            </div>

            <!-- ======================================================
    WELCOME CARD
    ====================================================== -->

            <div class="card mt-4 p-4 shadow-sm">

                <h4>
                    Bienvenue <?= htmlspecialchars($user['nom']) ?> 👋
                </h4>

                <p class="text-muted mb-0">
                    Gérez vos services, vos réservations et votre activité professionnelle.
                </p>

            </div>

            <!-- ======================================================
    SERVICES LIST
    ====================================================== -->

            <div class="card mt-4 p-4 shadow-sm">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h4 class="mb-0">
                        Mes services
                    </h4>

                    <a href="add-services.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        Ajouter
                    </a>

                </div>


                <?php if (!empty($services)): ?>

                    <div class="row">

                        <?php foreach ($services as $service): ?>

                            <div class="mt-2 d-flex gap-2">

                                <a href="edit-service.php?id=<?= $service['id'] ?>"
                                    class="btn btn-warning btn-sm">
                                    Modifier
                                </a>

                                <a href="delete-service.php?id=<?= $service['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Voulez-vous vraiment supprimer ce service ?');">
                                    Supprimer
                                </a>

                                <!-- ✅ BOUTON AJOUT DISPONIBILITÉ -->
                                <a href="disponibilites.php?service=<?= $service['id'] ?>"
                                    class="btn btn-success btn-sm">
                                    + Disponibilité
                                </a>

                            </div>

                            <div class="col-md-4 mb-3">

                            <br>

                                <div class="card p-3 shadow-sm">

                                    <h5>
                                        <?= htmlspecialchars($service['nom_service']) ?>
                                    </h5>

                                    <p class="text-muted">
                                        <?= htmlspecialchars($service['description']) ?>
                                    </p>


                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="text-muted">
                        Aucun service ajouté pour le moment.
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <?php include("../includes/footer.php"); ?>
</body>

</html>