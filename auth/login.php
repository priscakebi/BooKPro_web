<?php
// ======================================================
// DÉMARRAGE DE LA SESSION
// ======================================================
session_start();

// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================
require("../config/database.php");

// ======================================================
// MESSAGE D'ERREUR
// ======================================================
$message = "";

// ======================================================
// TRAITEMENT DU FORMULAIRE
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Récupération des données
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Recherche de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    

    // Vérification de l'utilisateur et du mot de passe
    if ($user && password_verify($password, $user["password"])) {

        // Création des variables de session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nom"] = $user["nom"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        // Sécurité
        session_regenerate_id(true);

        // Redirection selon le rôle
        switch ($user["role"]) {

            case "admin":
                header("Location: ../admin/admin-dashboard.php");
                exit();

            case "professionnel":
                header("Location: ../pro/pro-dashboard.php");
                exit();

            case "client":
                header("Location: ../user/dashboard.php");
                echo "connexion reussie";
                exit();

            default:
                // Si le rôle est vide ou inconnu
                session_destroy();
                $message = "
                    <div class='alert alert-danger'>
                        Ce compte n'a pas de rôle valide.
                    </div>
                ";
        }

    } else {

        $message = "
            <div class='alert alert-danger'>
                Email ou mot de passe incorrect.
            </div>
        ";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <!-- ==================================================
    META
    ================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion - BookPro</title>

    <!-- ==================================================
    BOOTSTRAP
    ================================================== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ==================================================
    CSS PERSONNALISÉ
    ================================================== -->
    <link rel="stylesheet" href="../assets/login.css">

</head>

<body>

<section class="login-page">

    <div class="container">

        <div class="row justify-content-center align-items-center min-vh-100">

            <div class="col-md-5">

                <div class="card shadow-lg p-4">

                    <!-- Logo -->
                    <h1 class="text-center mb-2">
                        BookPro
                    </h1>

                    <h4 class="text-center mb-4">
                        Connexion
                    </h4>

                    <!-- Message -->
                    <?= $message ?>

                    <!-- Formulaire -->
                    <form method="POST">

                        <!-- Email -->
                        <input
                            type="email"
                            name="email"
                            class="form-control mb-3"
                            placeholder="Adresse email"
                            required>

                        <!-- Mot de passe -->
                        <input
                            type="password"
                            name="password"
                            class="form-control mb-2"
                            placeholder="Mot de passe"
                            required>

                        <!-- Mot de passe oublié -->
                        <div class="text-end mb-3">
                            <a href="forgot-password.php">
                                Mot de passe oublié ?
                            </a>
                        </div>

                        <!-- Bouton -->
                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Se connecter
                        </button>

                    </form>

                    <!-- Création de compte -->
                    <p class="text-center mt-4">
                        Pas encore de compte ?
                        <a href="register.php">
                            Créer un compte
                        </a>
                    </p>

                    <!-- Retour -->
                    <p class="text-center">
                        <a href="../index.php">
                            ← Retour à l'accueil
                        </a>
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

</body>
</html>