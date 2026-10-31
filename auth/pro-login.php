<?php
// ======================================================
// CONNEXION DES PROFESSIONNELS
// ======================================================
require_once("../includes/auth.php");
require("../config/database.php");
require("../includes/connexion.php");

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

// Déjà connecté : direction son espace
if (isset($_SESSION["user_id"])) {
    redirect($next ?? dashboard_path($_SESSION["role"]));
}

$erreur = null;
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $email = trim($_POST["email"] ?? "");

    [$user, $erreur] = tenter_connexion($pdo, $email, $_POST["password"] ?? "", ['professionnel']);

    if ($user) {
        // Uniquement une page de l'espace pro comme retour
        redirect($next && str_starts_with($next, '/pro/') ? $next : "/pro/pro-dashboard.php");
    }
}

$pageTitle = "Connexion professionnelle";
$sideTitle = "Votre agenda, vos clients, votre activité.";
$sidePerks = [
    ['bi-calendar-week', 'Agenda en temps réel', 'Demandes, rendez-vous et créneaux libres en un coup d\'œil.'],
    ['bi-bell', 'Alertes instantanées', 'Confirmez ou refusez une demande directement depuis la notification.'],
    ['bi-graph-up-arrow', 'Statistiques', 'Chiffre d\'affaires, remplissage, provenance de vos clients.'],
];
include("../includes/auth_header.php");
?>

<span class="eyebrow"><i class="bi bi-briefcase-fill me-1"></i> Espace professionnel</span>
<h1 class="mb-1 mt-1">Connexion pro</h1>
<p class="text-muted mb-4">Accédez à votre agenda et à vos réservations.</p>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($erreur) ?>
        <?php if (str_contains($erreur, 'compte client')): ?>
            <a href="login.php" class="alert-link d-block mt-1">Aller à la connexion client →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST">

    <?= csrf_field() ?>

    <?php if ($next): ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php endif; ?>

    <label class="form-label">Email professionnel</label>
    <div class="input-icon mb-3">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" class="form-control" placeholder="contact@entreprise.fr" value="<?= e($email) ?>" required autofocus autocomplete="username">
    </div>

    <div class="d-flex justify-content-between">
        <label class="form-label">Mot de passe</label>
        <a href="forgot-password.php" class="small fw-semibold text-decoration-none">Mot de passe oublié ?</a>
    </div>
    <div class="input-icon mb-4">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-fill"></i></button>
    </div>

    <button type="submit" class="btn btn-gradient btn-lg w-100">Accéder à mon espace pro</button>

</form>

<div class="text-center mt-4">
    <p class="mb-1">Pas encore inscrit ? <a href="pro-register.php" class="fw-semibold">Créer mon profil pro</a></p>
    <p class="mb-0 small text-muted">Vous êtes client ? <a href="login.php" class="fw-semibold">Connexion client</a></p>
</div>

<?php include("../includes/auth_footer.php"); ?>
