<?php
session_start();
require("../config/database.php");

// ======================================================
// SECURITE ADMIN
// ======================================================
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// ACTION : SUPPRESSION
// ======================================================
if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];

    $del = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $del->execute([$id]);

    header("Location: gestion-reservations.php");
    exit();
}

// ======================================================
// ACTION : CHANGEMENT STATUT
// ======================================================
if (isset($_GET["status"]) && isset($_GET["id"])) {

    $id = (int) $_GET["id"];
    $status = $_GET["status"];

    $allowed = ["en_attente", "confirmee", "annulee"];

    if (in_array($status, $allowed)) {

        $upd = $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
        $upd->execute([$status, $id]);
    }

    header("Location: gestion-reservations.php");
    exit();
}

// ======================================================
// USER
// ======================================================
$user = [
    "id" => $_SESSION["user_id"],
    "nom" => $_SESSION["nom"],
    "role" => $_SESSION["role"]
];

// ======================================================
// RESERVATIONS
// ======================================================
$stmt = $pdo->query("
    SELECT 
        r.*,
        u.nom AS client_nom,
        s.nom_service,
        p.nom AS pro_nom,
        d.date_disponible,
        d.heure_debut,
        d.heure_fin
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    JOIN disponibilites d ON d.id = r.disponibilite_id
    JOIN services s ON s.id = d.service_id
    JOIN users p ON p.id = s.user_id
    ORDER BY r.id DESC
");

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Admin - Réservations</title>

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
CONTENT
====================================================== -->
        <div class="content p-4 w-100">

            <h2 class="mb-4">Gestion des réservations</h2>

            <table class="table table-bordered table-hover">

                <thead class="table-dark">
                    <tr>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Pro</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($reservations as $r): ?>

                        <tr>

                            <td><?= htmlspecialchars($r["client_nom"]) ?></td>
                            <td><?= htmlspecialchars($r["nom_service"]) ?></td>
                            <td><?= htmlspecialchars($r["pro_nom"]) ?></td>
                            <td><?= $r["date_disponible"] ?></td>

                            <td>
                                <?= substr($r["heure_debut"], 0, 5) ?>
                
                                <?= substr($r["heure_fin"], 0, 5) ?>
                            </td>

                            <!-- STATUT -->
                            <td>
                                <?php if ($r["statut"] == "en_attente"): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php elseif ($r["statut"] == "confirmee"): ?>
                                    <span class="badge bg-success">Confirmée</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Annulée</span>
                                <?php endif; ?>
                            </td>

                            <!-- ACTIONS -->
                            <td class="d-flex gap-1">

                                <!-- CONFIRMER -->
                                <a href="?id=<?= $r['id'] ?>&status=confirmee"
                                    class="btn btn-success btn-sm">
                                    ✔
                                </a>

                                <!-- ANNULER -->
                                <a href="?id=<?= $r['id'] ?>&status=annulee"
                                    class="btn btn-warning btn-sm">
                                    ✖
                                </a>

                                <!-- SUPPRIMER -->
                                <a href="?delete=<?= $r['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Supprimer cette réservation ?')">
                                    🗑
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