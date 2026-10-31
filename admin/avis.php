<?php

// ======================================================
// MODÉRATION DES AVIS (administrateurs uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('admin');

require("../config/database.php");

// ======================================================
// SUPPRESSION D'UN AVIS (contenu abusif, faux avis...)
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $delete = $pdo->prepare("DELETE FROM avis WHERE id = ?");
    $delete->execute([(int) ($_POST['id'] ?? 0)]);

    flash($delete->rowCount() ? "Avis supprimé." : "Avis introuvable.", $delete->rowCount() ? "success" : "danger");

    redirect("/admin/avis.php?" . http_build_query(array_intersect_key($_GET, array_flip(['note', 'q']))));
}

// ======================================================
// LISTE (filtre par note, recherche)
// ======================================================

$filtre = in_array($_GET['note'] ?? '', ['bas', 'moyen', 'haut'], true) ? $_GET['note'] : '';
$recherche = trim($_GET['q'] ?? '');

$where = ["1"];
$params = [];

if ($filtre) {
    $where[] = ['bas' => 'a.note <= 2', 'moyen' => 'a.note = 3', 'haut' => 'a.note >= 4'][$filtre];
}
if ($recherche !== '') {
    $where[] = "(a.commentaire LIKE ? OR c.nom LIKE ? OR p.nom LIKE ? OR p.entreprise LIKE ?)";
    array_push($params, ...array_fill(0, 4, '%' . $recherche . '%'));
}

$stmt = $pdo->prepare("
    SELECT a.id, a.note, a.commentaire, a.created_at, c.nom AS client, c.email AS client_email,
           p.id AS pro_id, COALESCE(p.entreprise, p.nom) AS pro, s.nom_service, d.date_disponible
    FROM avis a
    JOIN users c ON a.user_id = c.id
    JOIN reservations r ON a.reservation_id = r.id
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users p ON s.user_id = p.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$avis = $stmt->fetchAll();

$repartition = $pdo->query("SELECT note, COUNT(*) FROM avis GROUP BY note")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalAvis = array_sum($repartition);

$pageTitle = "Avis";
$pageSubtitle = "Modération des avis clients. Seuls les clients ayant eu un rendez-vous confirmé peuvent en laisser.";
$active = "avis";
include("../includes/dash_header.php");

?>

<div class="row g-4">

    <div class="col-xl-3">

        <!-- RÉPARTITION DES NOTES -->
        <div class="bp-card mb-4">
            <h5 class="mb-3">Répartition</h5>
            <?php for ($n = 5; $n >= 1; $n--): ?>
                <?php $nb = (int) ($repartition[$n] ?? 0); ?>
                <div class="d-flex align-items-center gap-2 small mb-2">
                    <span class="text-nowrap" style="width: 34px"><?= $n ?> <i class="bi bi-star-fill text-warning"></i></span>
                    <div class="progress flex-grow-1" style="height: 8px"><div class="progress-bar" style="width: <?= $totalAvis ? $nb / $totalAvis * 100 : 0 ?>%; background: var(--bp-primary)"></div></div>
                    <span class="text-muted text-end" style="width: 34px"><?= $nb ?></span>
                </div>
            <?php endfor; ?>
        </div>

    </div>

    <div class="col-xl-9">

        <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach (['' => 'Tous', 'bas' => '1-2 ★', 'moyen' => '3 ★', 'haut' => '4-5 ★'] as $val => $label): ?>
                <a href="?<?= http_build_query(array_filter(['note' => $val, 'q' => $recherche])) ?>" class="chip <?= $filtre === $val ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <div class="input-icon flex-grow-1" style="min-width: 220px">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Rechercher dans les avis..." value="<?= e($recherche) ?>">
                <?php if ($filtre): ?><input type="hidden" name="note" value="<?= e($filtre) ?>"><?php endif; ?>
            </div>
        </form>

        <div class="bp-card">

            <?php if (!$avis): ?>
                <div class="empty-state"><i class="bi bi-chat-square"></i>Aucun avis ne correspond.</div>
            <?php endif; ?>

            <?php foreach ($avis as $a): ?>
                <div class="d-flex gap-3 py-3 border-bottom">
                    <?= avatar(['nom' => $a['client']], 40) ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <span><strong><?= e($a['client']) ?></strong> <span class="text-muted">→</span>
                                <a href="<?= BASE_URL ?>/user/pro.php?id=<?= (int) $a['pro_id'] ?>" target="_blank" class="text-decoration-none"><?= e($a['pro']) ?></a></span>
                            <?= etoiles($a['note']) ?>
                        </div>
                        <div class="small text-muted mb-1"><?= e($a['nom_service']) ?> · RDV du <?= date_fr($a['date_disponible']) ?> · publié le <?= date_fr($a['created_at']) ?></div>
                        <p class="mb-0"><?= e($a['commentaire']) ?></p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Supprimer cet avis ? Il disparaîtra de la page du professionnel.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer l'avis"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (count($avis) === 100): ?>
                <p class="small text-muted mt-3 mb-0">Seuls les 100 avis les plus récents sont affichés : affinez avec les filtres.</p>
            <?php endif; ?>

        </div>

    </div>

</div>

<?php include("../includes/dash_footer.php"); ?>
