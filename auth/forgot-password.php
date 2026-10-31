<?php
require_once("../includes/auth.php");
require("../config/database.php");
require_once("../config/mail.php");

$envoye = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $email = trim($_POST["email"] ?? "");

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();

    if ($userId) {

        // Jeton aléatoire : seul son hash est stocké en base
        $token = bin2hex(random_bytes(32));

        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

        $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)")
            ->execute([$userId, hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $lien = $scheme . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/auth/reset-password.php?token=" . $token;

        sendMail($email, "Réinitialisation de votre mot de passe",
            "Bonjour,\n\nPour choisir un nouveau mot de passe, cliquez sur ce lien (valable 1 heure) :\n$lien\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.");
    }

    // Même message que le compte existe ou non (ne révèle pas les emails inscrits)
    $envoye = true;
}

$pageTitle = "Mot de passe oublié";
include("../includes/auth_header.php");
?>

<h1 class="mb-1">Mot de passe oublié</h1>
<p class="text-muted mb-4">Indiquez votre email, nous vous envoyons un lien pour en choisir un nouveau.</p>

<?php if ($envoye): ?>

    <div class="alert alert-success d-flex gap-2">
        <i class="bi bi-envelope-check-fill"></i>
        Si un compte existe avec cette adresse, un lien de réinitialisation vient d'être envoyé.
    </div>

<?php else: ?>

    <form method="POST">

        <?= csrf_field() ?>

        <div class="input-icon mb-4">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="vous@exemple.fr" required autofocus>
        </div>

        <button class="btn btn-primary btn-lg w-100">Envoyer le lien</button>

    </form>

<?php endif; ?>

<p class="text-center mt-4 mb-0"><a href="login.php" class="fw-semibold">Retour à la connexion</a></p>

<?php include("../includes/auth_footer.php"); ?>
