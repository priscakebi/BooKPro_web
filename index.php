<?php
require_once("includes/auth.php");
require("config/database.php");
require("includes/pros.php");

// ======================================================
// DONNÉES DE LA PAGE
// ======================================================

// Chiffres clés
$nbPros = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM services")->fetchColumn();
$nbServices = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$nbCreneaux = $pdo->query("
    SELECT COUNT(*) FROM disponibilites d
    WHERE CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
      AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)
")->fetchColumn();

// Nombre de pros par métier
$parMetier = $pdo->query("
    SELECT u.metier, COUNT(DISTINCT u.id) FROM users u
    JOIN services s ON s.user_id = u.id
    WHERE u.role = 'professionnel' GROUP BY u.metier
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Pros mis en avant
$pros = rechercher_pros($pdo, [], 6);

$pageTitle = "Réservez un professionnel près de chez vous";
$navTransparent = true;
include("includes/public_header.php");
?>

<!-- ======================================================
HERO
====================================================== -->
<section class="hero">

    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-7">

                <span class="hero-badge mb-4 animate-in"><i class="bi bi-lightning-charge-fill"></i> Réservation en ligne, sans appel</span>

                <h1 class="mb-4 animate-in delay-1">
                    Le bon pro,<br>au bon moment,<br><span style="color:#7cf2dc">au juste prix.</span>
                </h1>

                <p class="lead mb-5 animate-in delay-2">
                    Comparez les professionnels près de chez vous, consultez leurs tarifs et horaires, puis réservez un créneau libre en quelques secondes.
                </p>

                <!-- RECHERCHE -->
                <form class="search-bar animate-in delay-3" method="GET" action="user/services.php">

                    <div class="field">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" class="form-control" placeholder="Service, pro…">
                    </div>

                    <div class="field">
                        <i class="bi bi-briefcase"></i>
                        <select name="metier" class="form-select">
                            <option value="">Métier</option>
                            <?php foreach (METIERS as $m => $icon): ?>
                                <option value="<?= e($m) ?>"><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <i class="bi bi-geo-alt"></i>
                        <input type="text" name="ville" class="form-control" placeholder="Ville ou CP">
                    </div>

                    <button class="btn btn-primary btn-lg px-4">Rechercher</button>

                </form>

                <div class="hero-stats mt-5 animate-in delay-3">
                    <div><strong data-count="<?= (int) $nbPros ?>">0</strong><span>professionnels</span></div>
                    <div><strong data-count="<?= (int) $nbServices ?>">0</strong><span>services</span></div>
                    <div><strong data-count="<?= (int) $nbCreneaux ?>">0</strong><span>créneaux libres</span></div>
                </div>

            </div>

            <!-- VISUEL -->
            <div class="col-lg-5 d-none d-lg-block">

                <div class="hero-visual">

                    <div class="main-card">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="cat-icon m-0" style="width:52px;height:52px;background:rgba(255,255,255,.18);color:#fff"><i class="bi bi-droplet-half"></i></span>
                            <div>
                                <strong class="d-block">Votre plombier</strong>
                                <small class="opacity-75">Tarifs, horaires et avis visibles</small>
                            </div>
                        </div>
                        <p class="small opacity-75 mb-2">Créneaux disponibles demain</p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (['08:30', '10:00', '11:30', '14:00', '16:30'] as $i => $h): ?>
                                <span class="badge rounded-pill <?= $i === 2 ? 'bg-white text-dark' : 'border border-light' ?> px-3 py-2"><?= $h ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="hero-float" style="top:0; right:0;">
                        <i class="bi bi-check-circle-fill text-success"></i> <strong>Réservation confirmée</strong><br>
                        <small class="text-muted">Demain à 11:30</small>
                    </div>

                    <div class="hero-float" style="bottom:10px; left:0; animation-delay:-3s;">
                        <small class="text-muted d-block">À partir de</small>
                        <strong class="fs-5">45 €</strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ======================================================
MÉTIERS
====================================================== -->
<section class="section">

    <div class="container">

        <div class="text-center mb-5 reveal">
            <span class="eyebrow">Catégories</span>
            <h2 class="section-title mt-2">De quoi avez-vous besoin ?</h2>
        </div>

        <div class="row g-4 justify-content-center">

            <?php $i = 0; foreach (METIERS as $metier => $icon): ?>
                <div class="col-6 col-md-4 col-lg reveal reveal-delay-<?= $i++ % 4 ?>">
                    <a href="user/services.php?metier=<?= urlencode($metier) ?>" class="bp-card card-hover cat-card h-100">
                        <div class="cat-icon"><i class="bi <?= $icon ?>"></i></div>
                        <h5 class="mb-1"><?= e($metier) ?></h5>
                        <small class="text-muted"><?= (int) ($parMetier[$metier] ?? 0) ?> pro<?= ($parMetier[$metier] ?? 0) > 1 ? 's' : '' ?></small>
                    </a>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

</section>

<!-- ======================================================
PROS MIS EN AVANT
====================================================== -->
<?php if (!empty($pros)): ?>
<section class="section pt-0">

    <div class="container">

        <div class="d-flex justify-content-between align-items-end mb-5 reveal">
            <div>
                <span class="eyebrow">Ils sont disponibles</span>
                <h2 class="section-title mt-2 mb-0">Professionnels à découvrir</h2>
            </div>
            <a href="user/services.php" class="btn btn-outline-primary d-none d-md-inline-flex">Voir tous les pros <i class="bi bi-arrow-right ms-2"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($pros as $i => $pro): ?>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-<?= $i % 3 ?>">
                    <?php include("includes/pro_card.php"); ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</section>
<?php endif; ?>

<!-- ======================================================
COMMENT ÇA MARCHE
====================================================== -->
<section class="section bg-white">

    <div class="container">

        <div class="text-center mb-5 reveal">
            <span class="eyebrow">Simple et rapide</span>
            <h2 class="section-title mt-2">Comment ça marche ?</h2>
        </div>

        <div class="row g-4">

            <?php
            $etapes = [
                ['Recherchez', 'Filtrez par métier et par ville, comparez les tarifs, les avis et les horaires d\'ouverture.'],
                ['Réservez', 'Choisissez un créneau libre dans l\'agenda du professionnel. Aucun appel nécessaire.'],
                ['Profitez', 'Le pro confirme votre rendez-vous. Après l\'intervention, laissez votre avis.'],
            ];
            ?>

            <?php foreach ($etapes as $i => [$titre, $texte]): ?>
                <div class="col-md-4 reveal reveal-delay-<?= $i ?>">
                    <div class="bp-card h-100">
                        <div class="step-num"><?= $i + 1 ?></div>
                        <h4><?= $titre ?></h4>
                        <p class="text-muted mb-0"><?= $texte ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

</section>

<!-- ======================================================
APPEL AUX PROS
====================================================== -->
<section class="section">

    <div class="container">

        <div class="cta-pro reveal">

            <div class="row align-items-center g-4 position-relative" style="z-index:1">

                <div class="col-lg-8">
                    <span class="eyebrow text-white opacity-75">Vous êtes professionnel ?</span>
                    <h2 class="section-title mt-2">Remplissez votre agenda, sans effort.</h2>
                    <p class="mb-0 opacity-75 fs-5">Créez votre page, affichez vos tarifs et horaires, publiez vos disponibilités : les clients réservent directement en ligne.</p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <a href="auth/pro-register.php" class="btn btn-light btn-lg fw-bold">Créer mon profil pro <i class="bi bi-arrow-right ms-1"></i></a>
                </div>

            </div>

        </div>

    </div>

</section>

<?php include("includes/public_footer.php"); ?>
