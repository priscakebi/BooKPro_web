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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - BookPro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center mt-5">

        <div class="col-md-5">

            <div class="card p-4 shadow">

                <h2 class="text-center mb-4">Nouveau mot de passe</h2>

                <?php if (!$reset): ?>

                    <div class="alert alert-danger">Ce lien est invalide ou a expiré.</div>

                    <a href="forgot-password.php" class="btn btn-primary w-100">Demander un nouveau lien</a>

                <?php else: ?>

                    <?php if ($erreur): ?>
                        <div class="alert alert-danger"><?= e($erreur) ?></div>
                    <?php endif; ?>

                    <form method="POST">

                        <?= csrf_field() ?>

                        <input type="password" name="password" class="form-control mb-3"
                               placeholder="Nouveau mot de passe (8 caractères min.)" minlength="8" required>

                        <input type="password" name="confirm_password" class="form-control mb-3"
                               placeholder="Confirmer le mot de passe" required>

                        <button class="btn btn-primary w-100">Enregistrer</button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

</body>
</html>
