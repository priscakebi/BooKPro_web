<?php
session_start();
require("../config/database.php");

// ======================================================
// SECURITE
// ======================================================
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$user = [
    'id' => $_SESSION['user_id'],
    'nom' => $_SESSION['nom'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['role']
];

// ======================================================
// ACTION : SUPPRIMER
// ======================================================
if (isset($_GET["delete"])) {

    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET deleted_by_client = 1
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);

    header("Location: reservations.php");
    exit();
}

// ======================================================
// ACTION : ANNULER
// ======================================================
if (isset($_GET["cancel"])) {

$id = (int) $_GET["cancel"];

// récupérer réservation + disponibilité
$stmt = $pdo->prepare("
    SELECT disponibilite_id, statut
    FROM reservations
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$id, $user_id]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if ($res && $res["statut"] !== "annulee") {

    // annuler réservation
    $stmt = $pdo->prepare("
        UPDATE reservations
        SET statut = 'annulee'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, $user_id]);

    // remettre créneau dispo
    $stmt = $pdo->prepare("
        UPDATE disponibilites
        SET statut = 'disponible'
        WHERE id = ?
    ");
    $stmt->execute([$res["disponibilite_id"]]);
}

header("Location: reservations.php");
exit();

}



// ======================================================
// ACTION : MODIFIER (DATE + HEURE si en attente)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_id"])) {

    $id = (int) $_POST["edit_id"];
    $date = $_POST["date_reservation"];
    $heure = $_POST["heure_reservation"];

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET date_reservation = ?, heure_reservation = ?
        WHERE id = ? AND user_id = ? AND statut = 'en_attente'
    ");

    $stmt->execute([$date, $heure, $id, $user_id]);

    header("Location: reservations.php");
    exit();
}

// ======================================================
// LISTE RESERVATIONS CLIENT
// ======================================================
    $stmt = $pdo->prepare("
    SELECT
        r.*,
        s.nom_service,
        p.nom AS pro_nom,
        d.date_disponible,
        d.heure_debut,
        d.heure_fin
    FROM reservations r
    JOIN disponibilites d
        ON d.id = r.disponibilite_id
    JOIN services s
        ON s.id = d.service_id
    JOIN users p
        ON p.id = s.user_id
    WHERE r.user_id = ?
AND r.deleted_by_client = 0
");

$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes réservations</title>

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

            <a href="reservations.php" class="menu">
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

<!-- CONTENT -->
<div class="content p-4 w-100">

<h2 class="mb-4">Mes réservations</h2>

<?php if (empty($reservations)): ?>

    <div class="alert alert-info">
        Aucune réservation.
    </div>

<?php else: ?>

    <div class="row">

    <?php foreach ($reservations as $r): ?>

        <div class="col-md-6 mb-3">

            <div class="card p-3 shadow-sm">

                <h5><?= htmlspecialchars($r["nom_service"]) ?></h5>

                <p class="mb-1">
                    👨‍🔧 <?= htmlspecialchars($r["pro_nom"]) ?>
                </p>

                <p class="mb-1">
                    📅 <?= $r["date_disponible"] ?> à <?= substr($r["heure_debut"],0,5) ?>
                </p>

                <!-- STATUT -->
                <p>
                    <?php if ($r["statut"] == "en_attente"): ?>
                        <span class="badge bg-warning">En attente</span>
                    <?php elseif ($r["statut"] == "confirmee"): ?>
                        <span class="badge bg-success">Confirmée</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Annulée</span>
                    <?php endif; ?>
                </p>

                <!-- ACTIONS -->
                <div class="d-flex gap-2">

                    <!-- ANNULER -->
                    <?php if ($r["statut"] != "annulee"): ?>
                        <a href="?cancel=<?= $r["id"] ?>"
                           class="btn btn-warning btn-sm">
                            Annuler
                        </a>
                    <?php endif; ?>

                    <!-- SUPPRIMER -->
                    <a href="?delete=<?= $r["id"] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ?')">
                        Supprimer
                    </a>

                </div>

                <!-- MODIFIER (seulement si en attente) -->
                <?php if ($r["statut"] == "en_attente"): ?>

                    <hr>

                    <form method="POST" class="d-flex gap-2">

                        <input type="hidden" name="edit_id" value="<?= $r["id"] ?>">

                        <input type="date"
                               name="date_reservation"
                               value="<?= $r["date_disponible"] ?>"
                               class="form-control form-control-sm"
                               required>

                        <input type="time"
                               name="heure_reservation"
                               value="<?= substr($r["heure_debut"],0,5) ?>"
                               class="form-control form-control-sm"
                               required>

                        <button class="btn btn-primary btn-sm">
                            Modifier
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    <?php endforeach; ?>

    </div>

<?php endif; ?>

</div>

</div>

</body>
</html>