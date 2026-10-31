<?php
// ======================================================
// RECHERCHE DE PROFESSIONNELS (page publique)
// ======================================================

require_once("../includes/auth.php");
require("../config/database.php");
require("../includes/pros.php");

// Filtres (?q=...&metier=...&ville=... ; ?search= gardé pour les anciens liens)
$filtres = [
    'q'      => trim($_GET['q'] ?? $_GET['search'] ?? ''),
    'metier' => array_key_exists($_GET['metier'] ?? '', METIERS) ? $_GET['metier'] : '',
    'ville'  => trim($_GET['ville'] ?? ''),
];

$pros = rechercher_pros($pdo, $filtres);

// Lien vers la même recherche avec un autre métier
function lien_metier($metier, $filtres)
{
    return '?' . http_build_query(array_filter(['q' => $filtres['q'], 'ville' => $filtres['ville'], 'metier' => $metier]));
}

$pageTitle = "Trouver un professionnel";
include("../includes/public_header.php");
?>

<!-- ======================================================
EN-TÊTE + FILTRES
====================================================== -->
<section class="pt-5 pb-4" style="background: linear-gradient(180deg, #ecebff 0%, var(--bp-bg) 100%);">

    <div class="container pt-3">

        <h1 class="section-title mb-2">Trouvez votre professionnel</h1>
        <p class="text-muted mb-4">Comparez les tarifs, les avis et les disponibilités, puis réservez en ligne.</p>

        <form class="search-bar mb-4" method="GET">

            <div class="field">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control" placeholder="Service, nom, entreprise..." value="<?= e($filtres['q']) ?>">
            </div>

            <div class="field">
                <i class="bi bi-geo-alt"></i>
                <input type="text" name="ville" class="form-control" placeholder="Ville ou code postal" value="<?= e($filtres['ville']) ?>">
            </div>

            <?php if ($filtres['metier']): ?>
                <input type="hidden" name="metier" value="<?= e($filtres['metier']) ?>">
            <?php endif; ?>

            <button class="btn btn-primary btn-lg px-4">Rechercher</button>

        </form>

        <!-- Puces métiers -->
        <div class="d-flex flex-wrap gap-2">

            <a href="<?= e(lien_metier('', $filtres)) ?>" class="chip <?= $filtres['metier'] === '' ? 'active' : '' ?>">Tous</a>

            <?php foreach (METIERS as $metier => $icon): ?>
                <a href="<?= e(lien_metier($metier, $filtres)) ?>" class="chip <?= $filtres['metier'] === $metier ? 'active' : '' ?>">
                    <i class="bi <?= $icon ?>"></i> <?= e($metier) ?>
                </a>
            <?php endforeach; ?>

        </div>

    </div>

</section>

<!-- ======================================================
RÉSULTATS
====================================================== -->
<section class="container py-5">

    <?php include("../includes/alertes.php"); ?>

    <p class="text-muted fw-semibold mb-4">
        <?= count($pros) ?> professionnel<?= count($pros) > 1 ? 's' : '' ?> trouvé<?= count($pros) > 1 ? 's' : '' ?>
    </p>

    <?php if (empty($pros)): ?>

        <div class="bp-card empty-state">
            <i class="bi bi-search"></i>
            <h5 class="text-dark">Aucun professionnel ne correspond</h5>
            <p>Essayez un autre métier ou élargissez la zone de recherche.</p>
            <a href="services.php" class="btn btn-primary">Voir tous les pros</a>
        </div>

    <?php else: ?>

        <div class="row g-4">
            <?php foreach ($pros as $i => $pro): ?>
                <div class="col-md-6 col-lg-4 reveal reveal-delay-<?= $i % 3 ?>">
                    <?php include("../includes/pro_card.php"); ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</section>

<?php include("../includes/public_footer.php"); ?>
