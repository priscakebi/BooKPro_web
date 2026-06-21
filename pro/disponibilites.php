<?php
session_start();
require("../config/database.php");

// ======================================================
// SECURITE
// ======================================================
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "professionnel") {
    header("Location: ../auth/login.php");
    exit();
}

$user = [
    "id" => $_SESSION["user_id"],
    "nom" => $_SESSION["nom"]
];

// ======================================================
// AJOUT DISPONIBILITE
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $service_id = $_POST["service_id"];

    foreach ($_POST["date_disponible"] as $index => $date) {

        $heure_debut = $_POST["heure_debut"][$index];
        $heure_fin   = $_POST["heure_fin"][$index];

        $today = date('Y-m-d');
        $now = date('H:i');

        // ❌ date passée
        if ($date < $today) continue;

        // ❌ heure passée si même jour
        if ($date == $today && $heure_debut <= $now) continue;

        // ❌ heure invalide
        if ($heure_fin <= $heure_debut) continue;

        $stmt = $pdo->prepare("
            INSERT INTO disponibilites
            (service_id, date_disponible, heure_debut, heure_fin)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $service_id,
            $date,
            $heure_debut,
            $heure_fin
        ]);
    }

    header("Location: disponibilites.php");
    exit();
}

// ======================================================
// SERVICES DU PROFESSIONNEL (IMPORTANT)
// ======================================================
$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$user["id"]]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// DISPONIBILITES
// ======================================================
$stmt = $pdo->prepare("
    SELECT
        d.*,
        s.nom_service
    FROM disponibilites d
    INNER JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ?
    ORDER BY d.date_disponible ASC
");

$stmt->execute([$user["id"]]);
$disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Disponibilités</title>
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


        <div class="container mt-5">

            <h2 class="mb-4">Mes disponibilités</h2>

            <!-- FORMULAIRE -->
            <form method="POST">

                <div class="mb-3">
                    <label>Service</label>

                    <select name="service_id" class="form-select" required>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service["id"] ?>">
                                <?= htmlspecialchars($service["nom_service"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="date" name="date_disponible[]" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <input type="time" name="heure_debut[]" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <input type="time" name="heure_fin[]" class="form-control" required>
                    </div>
                </div>

                <button class="btn btn-primary">Enregistrer</button>

            </form>

            <hr>

            <!-- LISTE -->
            <table class="table table-bordered mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Statut</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($disponibilites as $d): ?>

                        <?php
                        $stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reservations 
    WHERE disponibilite_id = ?
    AND statut IN ('en_attente', 'confirmee')
");

                        $stmt->execute([$d["id"]]);
                        $isReserved = $stmt->fetchColumn() > 0;
                        ?>

                        <tr>
                            <td><?= htmlspecialchars($d["nom_service"]) ?></td>
                            <td><?= $d["date_disponible"] ?></td>
                            <td><?= $d["heure_debut"] ?></td>
                            <td><?= $d["heure_fin"] ?></td>

                            <td>
                                <?php if ($isReserved): ?>
                                    <span class="badge bg-danger">Réservé</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

</body>

</html>