<?php

// ======================================================
// PROFIL PUBLIC DU PRO : infos, présentation, photo
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$proId]);
$pro = $stmt->fetch();

$erreur = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $champs = ['nom', 'entreprise', 'telephone', 'metier', 'adresse', 'code_postal', 'ville', 'description'];
    foreach ($champs as $c) {
        $pro[$c] = trim($_POST[$c] ?? "");
    }

    if ($pro['nom'] === "" || $pro['telephone'] === "" || $pro['ville'] === "") {
        $erreur = "Le nom, le téléphone et la ville sont obligatoires.";
    } elseif (!array_key_exists($pro['metier'], METIERS)) {
        $erreur = "Métier invalide.";
    } elseif ($pro['code_postal'] !== "" && !preg_match('/^[0-9]{5}$/', $pro['code_postal'])) {
        $erreur = "Le code postal doit contenir 5 chiffres.";
    } elseif (mb_strlen($pro['description']) > 2000) {
        $erreur = "La présentation est limitée à 2000 caractères.";
    }

    // ==================================================
    // PHOTO (facultative) : JPG/PNG/WEBP, 2 Mo max
    // ==================================================
    $nouvellePhoto = null;

    if (!$erreur && !empty($_FILES['photo']['name'])) {

        $f = $_FILES['photo'];
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        // Type réel du fichier (on ne se fie pas à l'extension envoyée)
        $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : null;

        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $erreur = "La photo doit faire moins de 2 Mo.";
        } elseif (!isset($types[$mime]) || !getimagesize($f['tmp_name'])) {
            $erreur = "Formats acceptés : JPG, PNG ou WEBP.";
        } else {
            // Nom aléatoire : impossible de deviner ou d'écraser un autre fichier
            $nouvellePhoto = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            move_uploaded_file($f['tmp_name'], __DIR__ . "/../uploads/pros/" . $nouvellePhoto);
        }
    }

    $supprimerPhoto = !empty($_POST['supprimer_photo']);

    if (!$erreur) {

        $anciennePhoto = $pro['photo'];

        if ($nouvellePhoto) {
            $pro['photo'] = $nouvellePhoto;
        } elseif ($supprimerPhoto) {
            $pro['photo'] = null;
        }

        $pdo->prepare("
            UPDATE users
            SET nom = ?, entreprise = ?, telephone = ?, metier = ?, adresse = ?, code_postal = ?, ville = ?, description = ?, photo = ?
            WHERE id = ?
        ")->execute([
            $pro['nom'], $pro['entreprise'] ?: null, $pro['telephone'], $pro['metier'], $pro['adresse'] ?: null,
            $pro['code_postal'] ?: null, $pro['ville'], $pro['description'] ?: null, $pro['photo'], $proId
        ]);

        // Ancien fichier supprimé du disque
        if ($anciennePhoto && $anciennePhoto !== $pro['photo']) {
            @unlink(__DIR__ . "/../uploads/pros/" . basename($anciennePhoto));
        }

        // La sidebar affiche ces infos depuis la session
        unset($pro['password']);
        $_SESSION['user'] = $pro;

        flash("Profil enregistré.");
        redirect("/pro/profil.php");
    }
}

$pageTitle = "Mon profil";
$pageSubtitle = "Ces informations apparaissent sur votre page publique.";
$active = "profil";
$topAction = '<a href="' . BASE_URL . '/user/pro.php?id=' . (int) $proId . '" target="_blank" class="btn btn-soft"><i class="bi bi-eye me-1"></i> Voir ma page</a>';
include("../includes/dash_header.php");
?>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <?= csrf_field() ?>

    <div class="row g-4">

        <!-- PHOTO -->
        <div class="col-lg-4">

            <div class="bp-card text-center h-100">

                <h5 class="mb-4">Photo ou logo</h5>

                <div class="mb-3 d-flex justify-content-center">
                    <span id="photoPreview" class="d-inline-flex" data-size="140"><?= avatar($pro, 140) ?></span>
                </div>

                <label class="btn btn-soft">
                    <i class="bi bi-upload me-1"></i> Choisir une image
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="d-none" data-photo-preview="#photoPreview">
                </label>

                <p class="small text-muted mt-2 mb-0">JPG, PNG ou WEBP · 2 Mo max</p>

                <?php if ($pro['photo']): ?>
                    <div class="form-check d-inline-block mt-3">
                        <input class="form-check-input" type="checkbox" name="supprimer_photo" value="1" id="supprPhoto">
                        <label class="form-check-label small" for="supprPhoto">Retirer la photo</label>
                    </div>
                <?php endif; ?>

            </div>

        </div>

        <!-- INFOS -->
        <div class="col-lg-8">

            <div class="bp-card">

                <h5 class="mb-3">Activité</h5>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Nom de l'entreprise</label>
                        <input type="text" name="entreprise" class="form-control" value="<?= e($pro['entreprise']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Métier *</label>
                        <select name="metier" class="form-select" required>
                            <?php foreach (METIERS as $m => $icon): ?>
                                <option value="<?= e($m) ?>" <?= $pro['metier'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Présentation</label>
                        <textarea name="description" class="form-control" rows="5" maxlength="2000"
                                  placeholder="Votre expérience, vos spécialités, votre zone d'intervention, vos garanties..."><?= e($pro['description']) ?></textarea>
                        <div class="form-text">Une bonne présentation rassure les clients et augmente les réservations.</div>
                    </div>

                </div>

                <h5 class="mb-3">Coordonnées</h5>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" name="nom" class="form-control" value="<?= e($pro['nom']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Téléphone *</label>
                        <input type="tel" name="telephone" class="form-control" value="<?= e($pro['telephone']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" placeholder="N° et rue" value="<?= e($pro['adresse']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="code_postal" class="form-control" inputmode="numeric" maxlength="5" value="<?= e($pro['code_postal']) ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Ville *</label>
                        <input type="text" name="ville" class="form-control" value="<?= e($pro['ville']) ?>" required>
                    </div>

                </div>

                <p class="small text-muted"><i class="bi bi-shield-lock me-1"></i> Email (<?= e($pro['email']) ?>) et SIRET (<?= e($pro['siret']) ?>) ne sont pas affichés publiquement.</p>

                <button class="btn btn-primary btn-lg">Enregistrer</button>

            </div>

        </div>

    </div>

</form>

<?php include("../includes/dash_footer.php"); ?>
