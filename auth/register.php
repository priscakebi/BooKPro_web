<?php
session_start();

require_once("../config/database.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = trim(htmlspecialchars($_POST["nom"]));
    $email = trim(htmlspecialchars($_POST["email"]));
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if (!empty($nom) && !empty($email) && !empty($password) && !empty($confirmPassword)) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = '<div class="alert alert-danger">Adresse email invalide.</div>';

        } elseif ($password !== $confirmPassword) {

            $message = '<div class="alert alert-danger">Les mots de passe ne correspondent pas.</div>';

        } else {

            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {

                $message = '<div class="alert alert-danger">Cet email est déjà utilisé.</div>';

            } else {

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = $pdo->prepare("
                    INSERT INTO users (nom, email, password)
                    VALUES (?, ?, ?)
                ");

                if ($sql->execute([$nom, $email, $passwordHash])) {

                    $message = '<div class="alert alert-success">Compte créé avec succès.</div>';

                } else {

                    $message = '<div class="alert alert-danger">Une erreur est survenue.</div>';
                }
            }
        }

    } else {

        $message = '<div class="alert alert-danger">Tous les champs sont obligatoires.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

     <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/register.css">
</head>

<body class="register-page">

    <section class="container">

        <div class="row justify-content-center align-items-center min-vh-100">

            <div class="col-md-5 col-lg-4">

                <div class="register-card">

                    <div class="text-center mb-4">

                        <div class="logo">
                            BookPro
                        </div>

                        <h2>Créer un compte</h2>

                        <p class="subtitle">
                            Rejoignez BookPro gratuitement
                        </p>

                    </div>

                    <?= $message ?>

                    <form method="POST">

                        <!-- Nom -->

                        <div class="input-group mb-3">

                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i>
                            </span>

                            <input
                                type="text"
                                name="nom"
                                class="form-control"
                                placeholder="Nom complet"
                                value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>"
                                required>

                        </div>

                        <!-- Email -->

                        <div class="input-group mb-3">

                            <span class="input-group-text">
                                <i class="bi bi-envelope-fill"></i>
                            </span>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Adresse email"
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                required>

                        </div>

                        <!-- Mot de passe -->

                        <div class="input-group mb-3">

                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="Mot de passe"
                                required>

                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                onclick="togglePassword('password', this)">

                                <i class="bi bi-eye-fill"></i>

                            </button>

                        </div>

                        <!-- Confirmation -->

                        <div class="input-group mb-4">

                            <span class="input-group-text">
                                <i class="bi bi-shield-lock-fill"></i>
                            </span>

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control"
                                placeholder="Confirmer le mot de passe"
                                required>

                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                onclick="togglePassword('confirm_password', this)">

                                <i class="bi bi-eye-fill"></i>

                            </button>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            Créer mon compte

                        </button>

                    </form>

                    <div class="text-center mt-4">

                        Déjà inscrit ?

                        <a href="login.php">
                            Se connecter
                        </a>

                    </div>

                    <div class="text-center mt-2">

                        <a href="../index.php">
                            ← Retour à l'accueil
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <script>

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            const icon = button.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                icon.classList.remove("bi-eye-fill");
                icon.classList.add("bi-eye-slash-fill");

            } else {

                input.type = "password";

                icon.classList.remove("bi-eye-slash-fill");
                icon.classList.add("bi-eye-fill");
            }
        }

    </script>

</body>
</html>