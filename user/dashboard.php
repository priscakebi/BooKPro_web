<?php
// ======================================================
// SESSION - sécurise l'accès au dashboard
// ======================================================
session_start();

// ======================================================
// PROTECTION : si non connecté → login
// ======================================================
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// RÉCUPÉRATION UTILISATEUR CONNECTÉ
// ======================================================
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Client - BookPro</title>

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
    SIDEBAR
    ====================================================== -->
    <div class="sidebar p-3">

        <h3 class="text-white mb-4">
            BookPro
        </h3>

        <!-- USER INFO -->
        <div class="user-box mb-4">

            <i class="bi bi-person-circle"></i>

            <div>
                <small>Bienvenue</small><br>

                <!-- NOM SÉCURISÉ -->
                <strong><?= htmlspecialchars($user['nom']) ?></strong>
            </div>

        </div>

        <!-- MENU -->
        <a href="dashboard.php" class="menu active">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="#" class="menu">
            <i class="bi bi-calendar-event"></i> Mes réservations
        </a>

        <a href="#" class="menu">
            <i class="bi bi-clock-history"></i> Historique
        </a>

        <a href="#" class="menu">
            <i class="bi bi-gear"></i> Paramètres
        </a>

        <a href="../auth/logout.php" class="menu text-danger">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>

    </div>

    <!-- ======================================================
    CONTENT
    ====================================================== -->
    <div class="content p-4 w-100">

        <h2 class="mb-4">Tableau de bord</h2>

        <!-- ======================================================
        CARDS STATS (PAS DE DONNÉES BRUTES SQL)
        ====================================================== -->
        <div class="row">

            <div class="col-md-4">

                <div class="card stat-card">

                    <i class="bi bi-calendar-check"></i>

                    <h4>0</h4>

                    <p>Réservations</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card stat-card">

                    <i class="bi bi-hourglass-split"></i>

                    <h4>0</h4>

                    <p>En attente</p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card stat-card">

                    <i class="bi bi-check-circle"></i>

                    <h4>0</h4>

                    <p>Confirmées</p>

                </div>

            </div>

        </div>

        <!-- ======================================================
        SECTION BIENVENUE (UI PROPRE)
        ====================================================== -->
        <div class="card mt-4 p-4">

            <h4>Bienvenue <?= htmlspecialchars($user['nom']) ?> 👋</h4>

            <p class="text-muted mb-0">
                Ici tu peux gérer tes réservations, consulter ton historique et modifier ton profil.
            </p>

        </div>

    </div>

</div>

</body>
</html>