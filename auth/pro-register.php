<?php

// ======================================================
// INSCRIPTION D'UN PROFESSIONNEL
// ======================================================

require_once("../includes/auth.php");
require("../config/database.php");

$erreur = null;
$v = $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    csrf_check();

    // Valeurs brutes : on échappe uniquement à l'affichage
    $champs = ['nom', 'email', 'telephone', 'entreprise', 'siret', 'metier', 'ville', 'code_postal'];
    $d = [];
    foreach ($champs as $c) {
        $d[$c] = trim($_POST[$c] ?? "");
    }
    $d['siret'] = str_replace(' ', '', $d['siret']);

    $password = $_POST["password"] ?? "";

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$d['email']]);

    if (in_array("", [$d['nom'], $d['email'], $d['telephone'], $d['siret'], $d['metier'], $d['ville']], true) || $password === "") {
        $erreur = "Merci de remplir tous les champs obligatoires.";
    } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } elseif (!array_key_exists($d['metier'], METIERS)) {
        $erreur = "Métier invalide.";
    } elseif (!preg_match('/^[0-9]{14}$/', $d['siret'])) {
        $erreur = "Le numéro SIRET doit contenir exactement 14 chiffres.";
    } elseif ($d['code_postal'] !== "" && !preg_match('/^[0-9]{5}$/', $d['code_postal'])) {
        $erreur = "Le code postal doit contenir 5 chiffres.";
    } elseif (strlen($password) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== ($_POST["confirm_password"] ?? "")) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {

        $pdo->prepare("
            INSERT INTO users (nom, email, telephone, entreprise, siret, metier, ville, code_postal, password, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'professionnel')
        ")->execute([
            $d['nom'], $d['email'], $d['telephone'], $d['entreprise'] ?: null, $d['siret'],
            $d['metier'], $d['ville'], $d['code_postal'] ?: null, password_hash($password, PASSWORD_DEFAULT)
        ]);

        // Connexion directe, puis parcours de démarrage
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$pdo->lastInsertId()]);
        login_user($stmt->fetch());

        flash("Bienvenue ! Complétez votre profil en quelques étapes pour apparaître auprès des clients.");
        redirect("/pro/pro-dashboard.php");
    }
}

$pageTitle = "Devenir professionnel";
$wide = true;
$sideTitle = "Développez votre activité avec BookPro.";
$sidePerks = [
    ['bi-shop', 'Votre vitrine en ligne', 'Présentation, photo, services, tarifs et horaires.'],
    ['bi-calendar-week', 'Agenda rempli', 'Les clients réservent vos créneaux libres 24h/24.'],
    ['bi-shield-check', 'Vous gardez la main', 'Confirmez ou refusez chaque demande.'],
];
include("../includes/auth_header.php");
?>

<span class="eyebrow">Espace professionnel</span>
<h1 class="mb-1 mt-1">Créez votre profil pro</h1>
<p class="text-muted mb-4">Gratuit. Vous compléterez votre présentation, vos services et vos horaires juste après.</p>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST">

    <?= csrf_field() ?>

    <!-- ACTIVITÉ -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="bi bi-briefcase me-1"></i> Votre activité</h6>

    <div class="row g-3 mb-4">

        <div class="col-sm-6">
            <label class="form-label">Métier *</label>
            <select name="metier" class="form-select" required>
                <option value="">Choisissez…</option>
                <?php foreach (METIERS as $m => $icon): ?>
                    <option value="<?= e($m) ?>" <?= ($v['metier'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-sm-6">
            <label class="form-label">Nom de l'entreprise</label>
            <input type="text" name="entreprise" class="form-control" placeholder="Ex : Dupont Plomberie" value="<?= e($v['entreprise'] ?? '') ?>">
        </div>

        <div class="col-sm-6">
            <label class="form-label">SIRET *</label>
            <input type="text" name="siret" class="form-control" inputmode="numeric" maxlength="17" placeholder="14 chiffres" value="<?= e($v['siret'] ?? '') ?>" required>
        </div>

        <div class="col-sm-3">
            <label class="form-label">Code postal</label>
            <input type="text" name="code_postal" class="form-control" inputmode="numeric" maxlength="5" value="<?= e($v['code_postal'] ?? '') ?>">
        </div>

        <div class="col-sm-3">
            <label class="form-label">Ville *</label>
            <input type="text" name="ville" class="form-control" value="<?= e($v['ville'] ?? '') ?>" required>
        </div>

    </div>

    <!-- CONTACT -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="bi bi-person me-1"></i> Vos coordonnées</h6>

    <div class="row g-3 mb-4">

        <div class="col-sm-6">
            <label class="form-label">Nom complet *</label>
            <input type="text" name="nom" class="form-control" value="<?= e($v['nom'] ?? '') ?>" required>
        </div>

        <div class="col-sm-6">
            <label class="form-label">Téléphone *</label>
            <input type="tel" name="telephone" class="form-control" placeholder="06 12 34 56 78" value="<?= e($v['telephone'] ?? '') ?>" required>
        </div>

        <div class="col-12">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" placeholder="contact@entreprise.fr" value="<?= e($v['email'] ?? '') ?>" required>
        </div>

    </div>

    <!-- SÉCURITÉ -->
    <h6 class="text-muted text-uppercase small fw-bold mb-3"><i class="bi bi-lock me-1"></i> Mot de passe</h6>

    <div class="row g-3 mb-4">

        <div class="col-sm-6">
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" id="password" class="form-control" placeholder="8 caractères min." minlength="8" required>
                <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-fill"></i></button>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="input-icon">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Confirmation" required>
                <button type="button" class="eye-btn" onclick="togglePassword('confirmPassword', this)"><i class="bi bi-eye-fill"></i></button>
            </div>
        </div>

    </div>

    <button type="submit" class="btn btn-gradient btn-lg w-100">Créer mon profil professionnel <i class="bi bi-arrow-right ms-1"></i></button>

</form>

<p class="text-center mt-4 mb-0">Déjà inscrit ? <a href="login.php" class="fw-semibold">Se connecter</a></p>

<?php include("../includes/auth_footer.php"); ?>
