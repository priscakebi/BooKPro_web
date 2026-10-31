<?php
require_once("../includes/auth.php");
require("../config/database.php");

// ======================================================
// VÉRIFICATION DU JETON
// ======================================================

$token = $_GET["token"] ?? "";

$stmt = $pdo->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
$stmt->execute([hash('sha256', $token)]);
$reset = $stmt->fetch();

$erreur = null;

if ($reset && $_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $password = $_POST["password"] ?? "";

    if (strlen($password) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== ($_POST["confirm_password"] ?? "")) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {

        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);

        // Jeton à usage unique
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$reset['user_id']]);

        flash("Mot de passe modifié. Vous pouvez vous connecter.");
        redirect("/auth/login.php");
    }
}

$pageTitle = "Nouveau mot de passe";
include("../includes/auth_header.php");
?>

<h1 class="mb-4">Nouveau mot de passe</h1>

<?php if (!$reset): ?>

    <div class="alert alert-danger">Ce lien est invalide ou a expiré.</div>

    <a href="forgot-password.php" class="btn btn-primary btn-lg w-100">Demander un nouveau lien</a>

<?php else: ?>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">

        <?= csrf_field() ?>

        <div class="input-icon mb-3">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" id="password" class="form-control" placeholder="Nouveau mot de passe (8 caractères min.)" minlength="8" required>
            <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-fill"></i></button>
        </div>

        <div class="input-icon mb-4">
            <i class="bi bi-shield-lock"></i>
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirmer le mot de passe" required>
        </div>

        <button class="btn btn-primary btn-lg w-100">Enregistrer</button>

    </form>

<?php endif; ?>

<?php include("../includes/auth_footer.php"); ?>
