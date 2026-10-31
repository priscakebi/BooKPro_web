<?php
// ======================================================
// DÉMARRAGE DE LA SESSION + FONCTIONS COMMUNES
// ======================================================
require_once("../includes/auth.php");

// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================
require("../config/database.php");

// Déjà connecté : direction son espace
if (isset($_SESSION["user_id"])) {
    redirect(dashboard_path($_SESSION["role"]));
}

// ======================================================
// VARIABLE POUR LES MESSAGES
// ======================================================
$message = "";

// ======================================================
// TRAITEMENT DU FORMULAIRE DE CONNEXION
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    // ==================================================
    // RÉCUPÉRATION DES DONNÉES
    // ==================================================
    $email = trim($_POST["email"] ?? "");
    $pass = $_POST["password"] ?? "";

    // ==================================================
    // RECHERCHE DE L'UTILISATEUR
    // ==================================================
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    // Récupération de l'utilisateur
    $user = $stmt->fetch();

    // ==================================================
    // VÉRIFICATION DU MOT DE PASSE
    // ==================================================
    if ($user && password_verify($pass, $user["password"])) {

        // ==============================================
        // CRÉATION DES VARIABLES DE SESSION
        // ==============================================
        // Régénération de l'identifiant de session
        session_regenerate_id(true);

        // On ne garde pas le hash du mot de passe en session
        unset($user["password"]);

        $_SESSION["user"] = $user;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        // ==============================================
        // REDIRECTION SELON LE RÔLE
        // ==============================================
        // Client : directement vers les services pour réserver
        redirect($user["role"] === "client" ? "/user/services.php" : dashboard_path($user["role"]));

    } else {

        // Ralentit les tentatives de mot de passe en rafale
        sleep(1);

        // ==============================================
        // IDENTIFIANTS INCORRECTS
        // ==============================================
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
                    <?php include("../includes/alertes.php"); ?>

                    <?= $message ?>

                    <!-- Formulaire -->
                    <form method="POST">

                        <?= csrf_field() ?>

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