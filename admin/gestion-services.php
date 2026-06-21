<?php
session_start();
require("../config/database.php");

// SECURITE ADMIN
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
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

// SUPPRESSION SERVICE
if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: services.php");
    exit();
}

// RECUP SERVICES + PRO
$stmt = $pdo->query("
    SELECT services.*, users.nom AS pro_name
    FROM services
    JOIN users ON users.id = services.user_id
    ORDER BY services.id DESC
");

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<title>Gestion services</title>

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

    <h2>Gestion des services</h2>

    <table class="table table-bordered mt-3">

        <thead>
            <tr>
                <th>ID</th>
                <th>Service</th>
                <th>Pro</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($services as $s): ?>

            <tr>
                <td><?= $s["id"] ?></td>
                <td><?= htmlspecialchars($s["nom_service"]) ?></td>
                <td><?= htmlspecialchars($s["pro_name"]) ?></td>
                <td><?= htmlspecialchars($s["description"]) ?></td>
                <td><?= $s["prix"] ?> €</td>

                <td>

                    <!-- SUPPRIMER -->
                    <a href="?delete=<?= $s["id"] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ce service ?')">
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



