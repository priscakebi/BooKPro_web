<?php
require_once("../includes/auth.php");
require("../config/database.php");
require("../config/mail.php");

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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - BookPro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center mt-5">

        <div class="col-md-5">

            <div class="card p-4 shadow">

                <h2 class="text-center mb-4">
                    Mot de passe oublié
                </h2>

                <?php if ($envoye): ?>

                    <div class="alert alert-success">
                        Si un compte existe avec cette adresse, un lien de réinitialisation vient d'être envoyé.
                    </div>

                <?php else: ?>

                <form method="POST">

                    <?= csrf_field() ?>

                    <input
                        type="email"
                        name="email"
                        class="form-control mb-3"
                        placeholder="Votre email"
                        required>

                    <button class="btn btn-primary w-100">
                        Envoyer le lien
                    </button>

                </form>

                <?php endif; ?>

                <p class="text-center mt-3 mb-0">
                    <a href="login.php">← Retour à la connexion</a>
                </p>

            </div>

        </div>

    </div>

</div>

</body>
</html>
