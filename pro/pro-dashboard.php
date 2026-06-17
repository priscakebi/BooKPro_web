<?php

// ======================================================
// SESSION
// ======================================================

session_start();

// ======================================================
// SECURITE
// ======================================================

if (!isset($_SESSION['user'])) {

    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// BDD
// ======================================================

require("../config/database.php");

// ======================================================
// RECUPERATION DES SERVICES DU PROFESSIONNEL
// ======================================================

$stmt = $pdo->prepare("

    SELECT *
    FROM services
    WHERE user_id = ?

");

$stmt->execute([$_SESSION['user_id']]);

$services = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard Professionnel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/pro-dashboard.css">

</head>

<body>

<!-- ======================================================
SIDEBAR
====================================================== -->

<div class="sidebar">

    <h2>BookPro</h2>

    <a href="dashboard.php">
        Mes services
    </a>

    <a href="add-service.php">
        Ajouter un service
    </a>

    <a href="reservations.php">
        Réservations
    </a>

    <a href="../auth/logout.php">
        Déconnexion
    </a>

</div>

<!-- ======================================================
CONTENU
====================================================== -->

<div class="content">

    <h1>

        Tableau de bord professionnel

    </h1>

    <div class="card p-4">

        <h3>

            Mes services

        </h3>

        <a
            href="add-service.php"
            class="btn btn-primary mb-3">

            Ajouter un service

        </a>

        <table class="table table-bordered">

            <thead>

                <tr>

                    <th>Nom</th>

                    <th>Description</th>

                    <th>Horaires</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach($services as $service): ?>

                <tr>

                    <td>
                        <?= $service['nom_service'] ?>
                    </td>

                    <td>
                        <?= $service['description'] ?>
                    </td>

                    <td>
                        <?= $service['horaires'] ?>
                    </td>

                    <td>

                        <a
                            href="edit-service.php?id=<?= $service['id'] ?>"
                            class="btn btn-warning btn-sm">

                            Modifier

                        </a>

                        <a
                            href="delete-service.php?id=<?= $service['id'] ?>"
                            class="btn btn-danger btn-sm">

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