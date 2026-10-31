<?php
// ======================================================
// DÉMARRAGE DE LA SESSION + FONCTIONS COMMUNES
// ======================================================
require_once("../includes/auth.php");
require("../config/database.php");
require("../includes/connexion.php");

// Page à rouvrir après connexion (ex : la page du pro consultée)
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

// Déjà connecté : direction son espace
if (isset($_SESSION["user_id"])) {
    redirect($next ?? dashboard_path($_SESSION["role"]));
}

$erreur = null;
$email = "";

// ======================================================
// TRAITEMENT DU FORMULAIRE DE CONNEXION
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $email = trim($_POST["email"] ?? "");

    [$user, $erreur] = tenter_connexion($pdo, $email, $_POST["password"] ?? "");

    if ($user) {
        // Client : directement vers les pros pour réserver
        redirect($next ?? ($user["role"] === "client" ? "/user/services.php" : dashboard_path($user["role"])));
    }
}

$pageTitle = "Connexion";
include("../includes/auth_header.php");
?>

<h1 class="mb-1">Bon retour 👋</h1>
<p class="text-muted mb-4">Connectez-vous pour gérer vos réservations.</p>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST">

    <?= csrf_field() ?>

    <?php if ($next): ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php endif; ?>

    <label class="form-label">Adresse email</label>
    <div class="input-icon mb-3">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" class="form-control" placeholder="vous@exemple.fr" value="<?= e($email) ?>" required autofocus>
    </div>

    <div class="d-flex justify-content-between">
        <label class="form-label">Mot de passe</label>
        <a href="forgot-password.php" class="small fw-semibold text-decoration-none">Mot de passe oublié ?</a>
    </div>
    <div class="input-icon mb-4">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-fill"></i></button>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">Se connecter</button>

</form>

<div class="text-center mt-4">
    <p class="mb-1">Pas encore de compte ? <a href="register.php" class="fw-semibold">Créer un compte client</a></p>
    <p class="mb-0 small text-muted">Vous êtes professionnel ? <a href="pro-login.php" class="fw-semibold">Connexion pro</a> · <a href="pro-register.php" class="fw-semibold">Rejoindre BookPro</a></p>
</div>

<?php include("../includes/auth_footer.php"); ?>
