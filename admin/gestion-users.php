<?php
session_start();
require("../config/database.php");

// SECURITE ADMIN
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$user = [
    "id" => $_SESSION["user_id"],
    "nom" => $_SESSION["nom"],
    "email" => $_SESSION["email"],
    "role" => $_SESSION["role"]
];

// SUPPRESSION UTILISATEUR
if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: users.php");
    exit();
}

// RECUP USERS
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Gestion utilisateurs</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

<!-- CONTENT -->
<div class="content p-4 w-100">

    <h2>Gestion des utilisateurs</h2>

    <table class="table table-bordered mt-3">

        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($users as $u): ?>

            <tr>
                <td><?= $u["id"] ?></td>
                <td><?= htmlspecialchars($u["nom"]) ?></td>
                <td><?= htmlspecialchars($u["email"]) ?></td>
                <td><?= $u["role"] ?></td>

                <td>

                    <a href="?delete=<?= $u["id"] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer cet utilisateur ?')">
                       Supprimer
                    </a>

                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

</div>

</body>
</html>