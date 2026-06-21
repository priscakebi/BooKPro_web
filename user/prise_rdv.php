<?php
session_start();
session_start();
require("../config/database.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["role"] !== "client") {
    header("Location: ../index.php");
    exit();
}

// ======================================================
// SECURITE CLIENT
// ======================================================
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// SERVICE ID
// ======================================================
if (!isset($_GET["service"])) {
    die("Service introuvable");
}

$service_id = (int) $_GET["service"];

// ======================================================
// SERVICE
// ======================================================
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die("Service introuvable");
}

// ======================================================
// DISPONIBILITES
// ======================================================
$stmt = $pdo->prepare("
    SELECT d.*
    FROM disponibilites d
    WHERE d.service_id = ?
    AND d.id NOT IN (
        SELECT disponibilite_id
        FROM reservations
        WHERE statut IN ('en_attente', 'confirmee')
        AND deleted_by_client = 0
    )
    ORDER BY d.date_disponible ASC, d.heure_debut ASC
");

$stmt->execute([$service_id]);
$disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Prise de rendez-vous</title>
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

<div class="container mt-5">

    <h2 class="mb-4">
        Réserver : <?= htmlspecialchars($service["nom_service"]) ?>
    </h2>

    <?php if (empty($disponibilites)): ?>

        <div class="alert alert-warning">
            Aucun créneau disponible.
        </div>

    <?php else: ?>

        <form method="POST" action="confirmer-rdv.php">

            <div class="list-group">

                <?php foreach ($disponibilites as $d): ?>

                    <label class="list-group-item d-flex align-items-center">

                        <input type="radio"
                               name="disponibilite_id"
                               value="<?= $d['id'] ?>"
                               required>

                        <div class="ms-3">
                            <strong>
                                <?= date('d/m/Y', strtotime($d["date_disponible"])) ?>
                            </strong>
                            <span class="text-muted">
                                <?= substr($d["heure_debut"], 0, 5) ?>
                                - <?= substr($d["heure_fin"], 0, 5) ?>
                            </span>
                        </div>

                    </label>

                <?php endforeach; ?>

            </div>

            <input type="hidden" name="service_id" value="<?= $service_id ?>">

            <button class="btn btn-primary mt-4 w-100">
                Confirmer la réservation
            </button>

        </form>

    <?php endif; ?>

</div>

</body>
</html>