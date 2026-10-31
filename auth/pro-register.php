<?php

// ======================================================
// DÉMARRAGE DE LA SESSION
// ======================================================
require_once("../includes/auth.php");

// ======================================================
// CONNEXION À LA BASE DE DONNÉES
// ======================================================
require("../config/database.php");

// Métiers proposés (liste unique pour le formulaire et la validation)
const METIERS = ['Plombier', 'Électricien', 'Mécanicien', 'Menuisier', 'Peintre'];

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

    csrf_check();

    // Valeurs brutes : on échappe uniquement à l'affichage
    $nom = trim($_POST["nom"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $telephone = trim($_POST["telephone"] ?? "");

    $entreprise = trim($_POST["entreprise"] ?? "");

    $siret = trim($_POST["siret"] ?? "");

    $metier = trim($_POST["metier"] ?? "");

    $ville = trim($_POST["ville"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

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

        // Email déjà utilisé ?
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "<div class='alert danger'>Adresse email invalide.</div>";

        } elseif ($check->fetch()) {

            $message = "<div class='alert danger'>Cet email est déjà utilisé.</div>";

        } elseif (strlen($password) < 8) {

            $message = "<div class='alert danger'>Le mot de passe doit contenir au moins 8 caractères.</div>";

        } elseif (!in_array($metier, METIERS, true)) {

            $message = "<div class='alert danger'>Métier invalide.</div>";

        } elseif (!preg_match('/^[0-9]{14}$/', $siret)) {

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
                        VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, 'professionnel'
                        )

                    ");

                $insert->execute([
                    $nom,
                    $email,
                    $telephone,
                    $entreprise,
                    $siret,
                    $metier,
                    $ville,
                    $passwordHash
                ]);

                // ======================================
                // MESSAGE DE SUCCÈS
                // ======================================

                flash("Votre compte professionnel a été créé avec succès. Connectez-vous pour publier vos services.");

                redirect("/auth/login.php");
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

                <?= csrf_field() ?>

                <!-- NOM -->
                <div class="input-group-custom">

                    <div class="icon-box">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <input
                        type="text"
                        name="nom"
                        value="<?= e($_POST['nom'] ?? '') ?>"
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
                        value="<?= e($_POST['email'] ?? '') ?>"
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
                        value="<?= e($_POST['telephone'] ?? '') ?>"
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
                        value="<?= e($_POST['entreprise'] ?? '') ?>"
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
                        value="<?= e($_POST['siret'] ?? '') ?>"
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

                        <?php foreach (METIERS as $m): ?>
                            <option value="<?= e($m) ?>" <?= ($_POST['metier'] ?? '') === $m ? 'selected' : '' ?>>
                                <?= e($m) ?>
                            </option>
                        <?php endforeach; ?>

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
                        value="<?= e($_POST['ville'] ?? '') ?>"
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

    <script src="../assets/app.js"></script>

</body>

</html>
