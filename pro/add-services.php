<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("_service.php");

$service = [];
$erreur = null;

// ======================================================
// AJOUT SERVICE
// ======================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    csrf_check();

    [$service, $erreur] = validate_service($_POST);

    if ($erreur === null) {

        $insert = $pdo->prepare("

            INSERT INTO services (user_id, nom_service, description, prix, duree)
            VALUES (?, ?, ?, ?, ?)

        ");

        $insert->execute([
            $_SESSION['user_id'],
            $service['nom_service'],
            $service['description'],
            $service['prix'],
            $service['duree']
        ]);

        flash("Service ajouté. Pensez à publier vos disponibilités.");

        redirect("/pro/services.php");
    }
}

$pageTitle = "Ajouter un service";
$active = "services";
include("../includes/dash_header.php");

?>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><?= e($erreur) ?></div>
<?php endif; ?>

<?php $submitLabel = "Ajouter"; include("_service_form.php"); ?>

<?php include("../includes/dash_footer.php"); ?>
