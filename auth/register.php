<?php
require_once("../includes/auth.php");
require_once("../config/database.php");

$erreur = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    csrf_check();

    $nom = trim($_POST["nom"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($nom === "" || $email === "" || $password === "") {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } elseif (strlen($password) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $confirmPassword) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {

        $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'client')")
            ->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);

        // Connexion directe après inscription
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$pdo->lastInsertId()]);
        login_user($stmt->fetch());

        flash("Bienvenue sur BookPro ! Trouvez votre professionnel.");
        redirect(safe_next($_POST['next'] ?? null) ?? "/user/services.php");
    }
}

$pageTitle = "Créer un compte";
include("../includes/auth_header.php");
?>

<h1 class="mb-1">Créer un compte</h1>
<p class="text-muted mb-4">Gratuit, en 30 secondes. Réservez ensuite en un clic.</p>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST">

    <?= csrf_field() ?>

    <?php if ($next = safe_next($_GET['next'] ?? $_POST['next'] ?? null)): ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php endif; ?>

    <label class="form-label">Nom complet</label>
    <div class="input-icon mb-3">
        <i class="bi bi-person"></i>
        <input type="text" name="nom" class="form-control" placeholder="Marie Dupont" value="<?= e($_POST['nom'] ?? '') ?>" required>
    </div>

    <label class="form-label">Adresse email</label>
    <div class="input-icon mb-3">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" class="form-control" placeholder="vous@exemple.fr" value="<?= e($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label class="form-label">Mot de passe</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" id="password" class="form-control" placeholder="8 caractères min." minlength="8" required>
                <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-fill"></i></button>
            </div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Confirmation</label>
            <div class="input-icon">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                <button type="button" class="eye-btn" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye-fill"></i></button>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">Créer mon compte</button>

</form>

<div class="text-center mt-4">
    <p class="mb-1">Déjà inscrit ? <a href="login.php" class="fw-semibold">Se connecter</a></p>
    <p class="mb-0 small text-muted">Vous êtes professionnel ? <a href="pro-register.php" class="fw-semibold">Créer un profil pro</a></p>
</div>

<?php include("../includes/auth_footer.php"); ?>
