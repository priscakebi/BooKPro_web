<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("_service.php");

// ======================================================
// RECUPERATION DU SERVICE (seulement s'il appartient au pro)
// ======================================================

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

$service = $stmt->fetch();

if (!$service) {
    flash("Service introuvable.", "danger");
    redirect("/pro/services.php");
}

$erreur = null;

// ======================================================
// MODIFICATION
// ======================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    csrf_check();

    [$service, $erreur] = validate_service($_POST);

    if ($erreur === null) {

        $update = $pdo->prepare("

            UPDATE services
            SET nom_service = ?, description = ?, prix = ?, duree = ?
            WHERE id = ? AND user_id = ?

        ");

        $update->execute([
            $service['nom_service'],
            $service['description'],
            $service['prix'],
            $service['duree'],
            $id,
            $_SESSION['user_id']
        ]);

        flash("Service modifié.");

        redirect("/pro/services.php");
    }
}

$pageTitle = "Modifier le service";
$active = "services";
include("../includes/dash_header.php");

?>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><?= e($erreur) ?></div>
<?php endif; ?>

<?php $submitLabel = "Enregistrer"; include("_service_form.php"); ?>

<?php include("../includes/dash_footer.php"); ?>
