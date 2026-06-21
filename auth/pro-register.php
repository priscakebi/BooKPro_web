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
// MESSAGE D'ERREUR OU DE SUCCÈS
// ======================================================
$message = "";

// ======================================================
// TRAITEMENT DU FORMULAIRE
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {



    // ==================================================
    // RÉCUPÉRATION DES DONNÉES
    // ==================================================

    $nom = htmlspecialchars($_POST["nom"]);

    $email = htmlspecialchars($_POST["email"]);

    $telephone = htmlspecialchars($_POST["telephone"]);

    $entreprise = htmlspecialchars($_POST["entreprise"]);

    $siret = htmlspecialchars($_POST["siret"]);

    $metier = htmlspecialchars($_POST["metier"]);

    $ville = htmlspecialchars($_POST["ville"]);

    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    // ==================================================
    // VÉRIFICATION DES CHAMPS
    // ==================================================

    if (

        !empty($nom) &&
        !empty($email) &&
        !empty($telephone) &&
        !empty($entreprise) &&
        !empty($siret) &&
        !empty($metier) &&
        !empty($ville) &&
        !empty($password) &&
        !empty($confirmPassword)

    ) {

        // ==============================================
        // VÉRIFICATION DU FORMAT DU SIRET
        // ==============================================

        if (!preg_match('/^[0-9]{14}$/', $siret)) {

            $message = "

                <div class='alert danger'>

                    Le numéro SIRET doit contenir exactement 14 chiffres.

                </div>

            ";
        } else {

            // ======================================
            // VÉRIFICATION MOTS DE PASSE
            // ======================================

            if ($password !== $confirmPassword) {

                $message = "

                        <div class='alert danger'>

                            Les mots de passe ne correspondent pas.

                        </div>

                    ";
            } else {

                // ======================================
                // HASH DU MOT DE PASSE
                // ======================================

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // ======================================
                // INSERTION DU PROFESSIONNEL
                // ======================================

                $insert = $pdo->prepare("
    INSERT INTO users (
        nom,
        email,
        telephone,
        entreprise,
        siret,
        metier,
        ville,
        password,
        role
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert->execute([
    $nom,
    $email,
    $telephone,
    $entreprise,
    $siret,
    $metier,
    $ville,
    password_hash($password, PASSWORD_DEFAULT),
    "professionnel"
]);

                // ======================================
                // MESSAGE DE SUCCÈS
                // ======================================

                $message = "

                        <div class='alert success'>

                            Votre compte professionnel a été créé avec succès.

                        </div>

                    ";
            }
        }
    } else {

        // ==============================================
        // CHAMPS MANQUANTS
        // ==============================================

        $message = "

            <div class='alert danger'>

                Tous les champs sont obligatoires.

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

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Devenir professionnel - BookPro</title>

    <!-- ==================================================
    BOOTSTRAP
    ================================================== -->




    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">




    <!-- ==================================================
    CSS
    ================================================== -->

    <link rel="stylesheet" href="../assets/pro-register.css">


</head>

<body>

    <!-- ======================================================
PAGE INSCRIPTION PROFESSIONNEL
====================================================== -->

    <section class="auth-page">

        <div class="register-card">

            <!-- LOGO -->

            <div class="logo">
                BookPro
            </div>

            <!-- TITRE -->

            <h1 class="register-title">
                Devenir professionnel
            </h1>

            <!-- SOUS-TITRE -->

            <p class="register-subtitle">
                Rejoignez BookPro et développez votre activité.
            </p>

            <!-- MESSAGE -->

            <?= $message ?>

            <!-- FORMULAIRE -->

            <form method="POST">

                <!-- NOM -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="nom"
                        placeholder="Nom complet"
                        required>

                </div>

                <!-- EMAIL -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-envelope-fill"></i>
                    </div>

                    <input
                        type="email"
                        name="email"
                        placeholder="Adresse email"
                        required>

                </div>

                <!-- TELEPHONE -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-telephone-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="telephone"
                        placeholder="Téléphone"
                        required>

                </div>

                <!-- ENTREPRISE -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-building-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="entreprise"
                        placeholder="Nom de l'entreprise"
                        required>

                </div>

                <!-- SIRET -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="siret"
                        maxlength="14"
                        placeholder="Numéro SIRET"
                        required>

                </div>

                <!-- METIER -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>

                    <select name="metier" required>

                        <option value="">
                            Choisissez un métier
                        </option>

                        <option value="Plombier">
                            Plombier
                        </option>

                        <option value="Électricien">
                            Électricien
                        </option>

                        <option value="Mécanicien">
                            Mécanicien
                        </option>

                        <option value="Menuisier">
                            Menuisier
                        </option>

                        <option value="Peintre">
                            Peintre
                        </option>

                    </select>

                </div>

                <!-- VILLE -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="ville"
                        placeholder="Ville"
                        required>

                </div>

                <!-- PASSWORD -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-lock-fill"></i>
                    </div>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Mot de passe"
                        required>

                    <button
                        type="button"
                        class="eye-btn"
                        onclick="togglePassword('password', this)">

                        <i class="bi bi-eye-fill"></i>

                    </button>

                </div>

                <!-- CONFIRM PASSWORD -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>

                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirm_password"
                        placeholder="Confirmer le mot de passe"
                        required>

                    <button
                        type="button"
                        class="eye-btn"
                        onclick="togglePassword('confirmPassword', this)">

                        <i class="bi bi-eye-fill"></i>

                    </button>

                </div>

                <!-- BOUTON -->
                <button
                    type="submit"
                    class="register-btn">

                    Créer mon compte professionnel

                </button>

            </form>

            <!-- ======================================================
LIENS BAS DE PAGE
====================================================== -->

            <div class="bottom-links">

                <!-- CONNEXION -->

                <p class="login-link">

                    Déjà inscrit ?

                    <a href="login.php">
                        Se connecter
                    </a>

                </p>

                <!-- RETOUR ACCUEIL -->

                <a href="../index.php" class="back-home">

                    <i class="bi bi-arrow-left"></i>

                    Retour à l'accueil

                </a>

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