<?php

// ======================================================
// SESSION
// ======================================================
session_start();

// ======================================================
// SÉCURITÉ
// ======================================================
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================
require("../config/database.php");

// ======================================================
// INFORMATIONS UTILISATEUR
// ======================================================
$user = [
    "id" => $_SESSION["user_id"],
    "nom" => $_SESSION["nom"],
    "email" => $_SESSION["email"],
    "role" => $_SESSION["role"]
];

// ======================================================
// VÉRIFICATION DE L'ID DU SERVICE
// ======================================================
if (!isset($_GET["id"])) {
    header("Location: pro-dashboard.php");
    exit();
}

$id = (int) $_GET["id"];

// ======================================================
// RÉCUPÉRATION DU SERVICE
// (uniquement s'il appartient au professionnel connecté)
// ======================================================
$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE id = ? AND user_id = ?
");

$stmt->execute([
    $id,
    $user["id"]
]);

$service = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le service n'existe pas
if (!$service) {
    die("Service introuvable.");
}

// ======================================================
// MESSAGE
// ======================================================
$message = "";

// ======================================================
// TRAITEMENT DU FORMULAIRE
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom_service = trim($_POST["nom_service"]);
    $description = trim($_POST["description"]);
    $prix = trim($_POST["prix"]);

    if (
        !empty($nom_service) &&
        !empty($description) &&
        $prix !== ""
    ) {

        // ==================================================
        // MISE À JOUR DU SERVICE
        // ==================================================
        $update = $pdo->prepare("
            UPDATE services
            SET
                nom_service = ?,
                description = ?,
                prix = ?
            WHERE id = ?
            AND user_id = ?
        ");

        $update->execute([
            $nom_service,
            $description,
            $prix,
            $id,
            $user["id"]
        ]);

        // Redirection vers le dashboard
        header("Location: pro-dashboard.php");
        exit();

    } else {

        $message = "
            <div class='alert alert-danger'>
                Tous les champs sont obligatoires.
            </div>
        ";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <!-- ======================================================
    META
    ====================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modifier un service</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
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

        <div class="user-box mb-4">

            <i class="bi bi-person-circle fs-1 text-white"></i>

            <div class="text-white ms-2">

                <small>Professionnel</small><br>

                <strong>
                    <?= htmlspecialchars($user["nom"]) ?>
                </strong>

            </div>

        </div>

        <a href="pro-dashboard.php" class="menu">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="add-services.php" class="menu">
            <i class="bi bi-plus-circle"></i>
            Ajouter service
        </a>

        <a href="gestion_rdv.php" class="menu">
            <i class="bi bi-calendar-event"></i>
            Réservations
        </a>

        <a href="#" class="menu">
            <i class="bi bi-gear"></i>
            Paramètres
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
            Modifier un service
        </h2>

        <?= $message ?>

        <div class="card shadow-sm p-4" style="max-width: 700px;">

            <form method="POST">

                <!-- NOM DU SERVICE -->
                <div class="mb-3">

                    <label class="form-label">
                        Nom du service
                    </label>

                    <input
                        type="text"
                        name="nom_service"
                        class="form-control"
                        value="<?= htmlspecialchars($service["nom_service"]) ?>"
                        required>

                </div>

                <!-- DESCRIPTION -->
                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        required><?= htmlspecialchars($service["description"]) ?></textarea>

                </div>

                <!-- PRIX -->
                <div class="mb-3">

                    <label class="form-label">
                        Prix (€)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="prix"
                        class="form-control"
                        value="<?= htmlspecialchars($service["prix"]) ?>"
                        required>

                </div>

                <!-- BOUTONS -->
                <button
                    type="submit"
                    class="btn btn-success">

                    <i class="bi bi-check-circle"></i>
                    Enregistrer

                </button>

                <a
                    href="pro-dashboard.php"
                    class="btn btn-secondary">

                    Annuler

                </a>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>

</body>

</html>