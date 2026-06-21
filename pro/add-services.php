<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}


// Vérifier que c'est un professionnel
if ($_SESSION["role"] != "professionnel") {
    header("Location: ../index.php");
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


require("../config/database.php");

$message = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom_service = trim($_POST["nom_service"]);
    $description = trim($_POST["description"]);
    $prix = trim($_POST["prix"]);

    if (!empty($nom_service) && !empty($description) && !empty($prix)) {

        $stmt = $pdo->prepare("
            INSERT INTO services (
                user_id,
                nom_service,
                description,
                prix
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION["user_id"],
            $nom_service,
            $description,
            $prix
        ]);

        $message = "
            <div class='alert alert-success'>
                Service ajouté avec succès !
            </div>
        ";

    } else {

        $message = "
            <div class='alert alert-danger'>
                Veuillez remplir tous les champs.
            </div>
        ";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ajouter un service</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/dashboard.css">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

            <a href="../pro/gestion-rdv.php" class="menu">
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

    <!-- CONTENU -->
    <div class="content p-4 w-100">

        <h2 class="mb-4">
            Ajouter un service
        </h2>

        <div class="card shadow p-4" style="max-width:700px;">

            <?= $message ?>

            <form method="POST">

                <!-- Nom du service -->
                <div class="mb-3">

                    <label class="form-label">
                        Nom du service
                    </label>

                    <input
                        type="text"
                        name="nom_service"
                        class="form-control"
                        placeholder="Ex : Réparation de fuite d'eau"
                        required>

                </div>

                <!-- Description -->
                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        placeholder="Décrivez votre prestation..."
                        required></textarea>

                </div>

                <!-- Prix -->
                <div class="mb-3">

                    <label class="form-label">
                        Prix (€)
                    </label>

                    <input
                        type="number"
                        name="prix"
                        class="form-control"
                        min="0"
                        step="0.01"
                        placeholder="Ex : 75"
                        required>

                </div>

                <!-- Bouton -->
                <button type="submit" class="btn btn-primary w-100">

                    <i class="bi bi-plus-circle"></i>
                    Ajouter le service

                </button>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>