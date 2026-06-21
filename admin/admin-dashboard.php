<?php

// ======================================================
// SESSION
// ======================================================
session_start();

// ======================================================
// SECURITE ADMIN
// ======================================================
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin'
) {
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
// STATISTIQUES
// ======================================================

// Nombre total d'utilisateurs
$usersCount = $pdo->query("
    SELECT COUNT(*) 
    FROM users
")->fetchColumn();

// Nombre de professionnels
$prosCount = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'professionnel'
")->fetchColumn();

// Nombre de clients
$clientsCount = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'client'
")->fetchColumn();

// Nombre total de services
$servicesCount = $pdo->query("
    SELECT COUNT(*)
    FROM services
")->fetchColumn();

// ======================================================
// DERNIERS UTILISATEURS
// ======================================================
$stmt = $pdo->query("
    SELECT *
    FROM users
    ORDER BY id DESC
    LIMIT 10
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - BookPro</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/admin-dashboard.css">

</head>

<body class="dashboard-page">

<div class="d-flex">

    <!-- ======================================================
    SIDEBAR ADMIN
    ====================================================== -->
    <div class="sidebar p-3">

        <h3 class="text-white mb-4">
            BookPro
        </h3>

        <div class="user-box mb-4">

            <i class="bi bi-shield-lock-fill fs-1 text-white"></i>

            <div class="text-white ms-2">

                <small>Administrateur</small><br>

                <strong>
                    <?= htmlspecialchars($user['nom']) ?>
                </strong>

            </div>

        </div>

        <!-- MENU -->
        <a href="admin-dashboard.php" class="menu active">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="gestion-users.php" class="menu">
            <i class="bi bi-people-fill"></i>
            Utilisateurs
        </a>

        <a href="gestion-services.php" class="menu">
            <i class="bi bi-briefcase-fill"></i>
            Services
        </a>

        <a href="gestion-reservations.php" class="menu">
            <i class="bi bi-calendar-event"></i>
            Réservations
        </a>

        <a href="../auth/logout.php" class="menu text-danger">
            <i class="bi bi-box-arrow-right"></i>
            Déconnexion
        </a>

        <div class="text-center mt-3">

            <a href="../index.php" class="text-white">
                ← Retour à l'accueil
            </a>

        </div>

    </div>

    <!-- ======================================================
    CONTENU
    ====================================================== -->
    <div class="content p-4 w-100">

        <h2 class="mb-4">
            Tableau de bord administrateur
        </h2>

        <!-- ======================================================
        STATISTIQUES
        ====================================================== -->
        <div class="row g-3">

            <div class="col-md-3">

                <div class="card stat-card text-center p-4">

                    <i class="bi bi-people-fill fs-1 text-primary"></i>

                    <h4 class="mt-2">
                        <?= $usersCount ?>
                    </h4>

                    <p class="text-muted mb-0">
                        Utilisateurs
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card stat-card text-center p-4">

                    <i class="bi bi-person-badge-fill fs-1 text-success"></i>

                    <h4 class="mt-2">
                        <?= $prosCount ?>
                    </h4>

                    <p class="text-muted mb-0">
                        Professionnels
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card stat-card text-center p-4">

                    <i class="bi bi-person-fill fs-1 text-warning"></i>

                    <h4 class="mt-2">
                        <?= $clientsCount ?>
                    </h4>

                    <p class="text-muted mb-0">
                        Clients
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card stat-card text-center p-4">

                    <i class="bi bi-briefcase-fill fs-1 text-danger"></i>

                    <h4 class="mt-2">
                        <?= $servicesCount ?>
                    </h4>

                    <p class="text-muted mb-0">
                        Services
                    </p>

                </div>

            </div>

        </div>

        <!-- ======================================================
        BIENVENUE
        ====================================================== -->
        <div class="card mt-4 p-4 shadow-sm">

            <h4>
                Bienvenue <?= htmlspecialchars($user['nom']) ?> 👋
            </h4>

            <p class="text-muted mb-0">
                Gérez les utilisateurs, les professionnels,
                les services et les réservations de BookPro.
            </p>

        </div>

        <!-- ======================================================
        DERNIERS UTILISATEURS
        ====================================================== -->
        <div class="card mt-4 p-4 shadow-sm">

            <h4 class="mb-3">
                Derniers utilisateurs inscrits
            </h4>

            <table class="table table-striped">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach($users as $u): ?>

                    <tr>

                        <td><?= $u['id'] ?></td>

                        <td>
                            <?= htmlspecialchars($u['nom']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($u['email']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($u['role']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>