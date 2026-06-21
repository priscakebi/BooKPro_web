<?php
// ======================================================
// SESSION - sécurise l'accès au dashboard
// ======================================================
session_start();

require("../config/database.php");

$user_id = $_SESSION["user_id"];

// TOTAL réservations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetchColumn();

// EN ATTENTE
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND statut = 'en_attente'");
$stmt->execute([$user_id]);
$attente = $stmt->fetchColumn();

// CONFIRMÉES
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND statut = 'confirmee'");
$stmt->execute([$user_id]);
$confirmees = $stmt->fetchColumn();

// ======================================================
// PROTECTION : si non connecté → login
// ======================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user = [
    'id' => $_SESSION['user_id'],
    'nom' => $_SESSION['nom'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['role']
];
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

            <a href="../user/reservations.php" class="menu">
                <i class="bi bi-calendar-event"></i> Mes réservations
            </a>



            <a href="../auth/logout.php" class="menu text-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>

            <div class="text-center mt-2">

                <a href="../index.php">
                    ← Retour à l'accueil
                </a>

            </div>

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

                        <h4><?= $total ?></h4>

                        <p>Réservations</p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card stat-card">

                        <i class="bi bi-hourglass-split"></i>

                        <h4><?= $attente ?></h4>

                        <p>En attente</p>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="card stat-card">

                        <i class="bi bi-check-circle"></i>

                        <h4><?= $confirmees ?></h4>

                        <p>Confirmées</p>

                    </div>

                </div>

            </div>

        <!-- ======================================================
CONTENT
====================================================== -->

            <h2 class="mb-4">Tableau de bord</h2>

            <!-- CARDS -->
            <div class="row">
                ...
            </div>

            <!-- BIENVENUE -->
            <div class="card mt-4 p-4">
                <h4>Bienvenue <?= htmlspecialchars($user['nom']) ?> 👋</h4>
                <p class="text-muted mb-0">
                    Ici tu peux gérer tes réservations, consulter ton historique.
                </p>
            </div>

            <!-- BOUTON RÉSERVER -->
            <div class="mt-4">
                <a href="../services/services.php" class="btn btn-primary">
                    Réserver un service
                </a>
            </div>

        </div>

    </div>

</body>

</html>