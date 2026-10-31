<?php

// ======================================================
// DISPONIBILITÉS : les créneaux réservables par les clients
// - génération automatique depuis les horaires d'ouverture
// - ajout manuel d'un créneau ponctuel
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

// Services du pro connecté
$stmt = $pdo->prepare("SELECT id, nom_service, duree FROM services WHERE user_id = ? ORDER BY nom_service");
$stmt->execute([$proId]);
$services = $stmt->fetchAll();
$servicesParId = array_column($services, null, 'id');

// Horaires d'ouverture
$stmt = $pdo->prepare("SELECT jour, heure_ouverture, heure_fermeture FROM horaires_ouverture WHERE user_id = ? ORDER BY heure_ouverture");
$stmt->execute([$proId]);
$ouverture = [];
foreach ($stmt->fetchAll() as $h) {
    $ouverture[$h['jour']][] = [heure($h['heure_ouverture']), heure($h['heure_fermeture'])];
}

// Un créneau du pro chevauche-t-il déjà [debut, fin[ ce jour-là ?
$overlap = $pdo->prepare("
    SELECT COUNT(*) FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND d.date_disponible = ? AND d.heure_debut < ? AND d.heure_fin > ?
");

$insert = $pdo->prepare("INSERT INTO disponibilites (service_id, date_disponible, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");

// ======================================================
// ACTIONS
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $action = $_POST['action'] ?? '';
    $serviceId = (int) ($_POST['service_id'] ?? 0);

    if (in_array($action, ['generer', 'ajouter'], true) && !isset($servicesParId[$serviceId])) {
        flash("Choisissez un de vos services.", "danger");
        redirect("/pro/horaires.php");
    }

    // ---------------- GÉNÉRATION AUTOMATIQUE ----------------
    if ($action === 'generer') {

        $du = $_POST['du'] ?? '';
        $au = $_POST['au'] ?? '';
        $duree = max(15, (int) ($_POST['duree'] ?? 60));
        $pause = max(0, (int) ($_POST['pause'] ?? 0));

        if (!$ouverture) {
            flash("Renseignez d'abord vos horaires d'ouverture.", "warning");
        } elseif (!$du || !$au || $au < $du || $du < date('Y-m-d')) {
            flash("Période invalide.", "danger");
        } elseif ((strtotime($au) - strtotime($du)) / 86400 > 92) {
            flash("Générez au maximum 3 mois à la fois.", "danger");
        } else {

            $crees = 0;
            $ignores = 0;

            $pdo->beginTransaction();

            for ($t = strtotime($du); $t <= strtotime($au); $t = strtotime('+1 day', $t)) {

                $date = date('Y-m-d', $t);

                foreach ($ouverture[date('N', $t)] ?? [] as [$ouv, $ferm]) {

                    // Découpe de la plage en créneaux de $duree minutes
                    for ($debut = strtotime("$date $ouv"); $debut + $duree * 60 <= strtotime("$date $ferm"); $debut += ($duree + $pause) * 60) {

                        $h1 = date('H:i', $debut);
                        $h2 = date('H:i', $debut + $duree * 60);

                        if ($debut <= time()) {
                            continue;
                        }

                        $overlap->execute([$proId, $date, $h2, $h1]);

                        if ($overlap->fetchColumn() > 0) {
                            $ignores++;
                            continue;
                        }

                        $insert->execute([$serviceId, $date, $h1, $h2]);
                        $crees++;
                    }
                }
            }

            $pdo->commit();

            flash("$crees créneau" . ($crees > 1 ? 'x' : '') . " créé" . ($crees > 1 ? 's' : '')
                . ($ignores ? " ($ignores ignoré" . ($ignores > 1 ? 's' : '') . " car déjà occupé" . ($ignores > 1 ? 's' : '') . ")" : "") . ".",
                $crees ? "success" : "warning");
        }

    // ---------------- AJOUT MANUEL ----------------
    } elseif ($action === 'ajouter') {

        $jour = $_POST['jour'] ?? "";
        $debut = $_POST['debut'] ?? "";
        $fin = $_POST['fin'] ?? "";

        if (!$jour || !$debut || !$fin) {
            flash("Tous les champs sont obligatoires.", "danger");
        } elseif ($jour . ' ' . $debut < date('Y-m-d H:i')) {
            flash("Le créneau ne peut pas être dans le passé.", "danger");
        } elseif ($fin <= $debut) {
            flash("L'heure de fin doit être après l'heure de début.", "danger");
        } else {

            $overlap->execute([$proId, $jour, $fin, $debut]);

            if ($overlap->fetchColumn() > 0) {
                flash("Ce créneau chevauche une disponibilité existante.", "danger");
            } else {
                $insert->execute([$serviceId, $jour, $debut, $fin]);
                flash("Créneau ajouté.");
            }
        }

    // ---------------- SUPPRESSION (créneau libre ou journée) ----------------
    } elseif ($action === 'supprimer' || $action === 'vider_jour') {

        $cond = $action === 'supprimer' ? "d.id = ?" : "d.date_disponible = ?";
        $valeur = $action === 'supprimer' ? (int) ($_POST['id'] ?? 0) : ($_POST['date'] ?? '');

        // Jamais un créneau qui a déjà été réservé (historique conservé)
        $delete = $pdo->prepare("
            DELETE d FROM disponibilites d
            JOIN services s ON d.service_id = s.id
            WHERE $cond AND s.user_id = ?
              AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id)
        ");
        $delete->execute([$valeur, $proId]);

        $n = $delete->rowCount();
        $n ? flash("$n créneau" . ($n > 1 ? 'x' : '') . " libre" . ($n > 1 ? 's' : '') . " supprimé" . ($n > 1 ? 's' : '') . ".")
           : flash("Rien à supprimer : les créneaux réservés sont conservés.", "warning");
    }

    // Retour à la page d'origine (ex : agenda) si elle est dans l'espace pro
    $retour = safe_next($_POST['retour'] ?? null);

    redirect($retour && str_starts_with($retour, '/pro/') ? $retour : "/pro/horaires.php");
}

// ======================================================
// CRÉNEAUX À VENIR, GROUPÉS PAR JOUR
// ======================================================

$stmt = $pdo->prepare("
    SELECT d.*, s.nom_service,
           (SELECT r.statut FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL) AS statut,
           (SELECT COUNT(*) FROM reservations r WHERE r.disponibilite_id = d.id) AS nb_resa
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND d.date_disponible >= CURDATE()
    ORDER BY d.date_disponible, d.heure_debut
");
$stmt->execute([$proId]);

$parJour = [];
foreach ($stmt->fetchAll() as $d) {
    $parJour[$d['date_disponible']][] = $d;
}

$pageTitle = "Disponibilités";
$pageSubtitle = "Les créneaux que vos clients peuvent réserver.";
$active = "horaires";
include("../includes/dash_header.php");
?>

<?php if (empty($services)): ?>

    <div class="bp-card empty-state">
        <i class="bi bi-tags"></i>
        <h5 class="text-dark">Ajoutez d'abord un service</h5>
        <p>Chaque créneau est rattaché à un de vos services (et à son prix).</p>
        <a href="add-services.php" class="btn btn-primary">Ajouter un service</a>
    </div>

<?php else: ?>

<div class="row g-4 mb-4">

    <!-- GÉNÉRATEUR -->
    <div class="col-xl-7">

        <form method="POST" class="bp-card h-100">

            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generer">

            <h5 class="mb-1"><i class="bi bi-magic text-primary me-1"></i> Générer depuis mes horaires</h5>
            <p class="text-muted small mb-3">Découpe vos horaires d'ouverture en créneaux sur la période choisie.</p>

            <?php if (!$ouverture): ?>
                <div class="alert alert-warning small mb-0">
                    Renseignez d'abord vos <a href="ouverture.php" class="fw-semibold">horaires d'ouverture</a>.
                </div>
            <?php else: ?>

            <div class="row g-3">

                <div class="col-md-12">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select" id="genService" required>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" data-duree="<?= (int) ($s['duree'] ?: 60) ?>"><?= e($s['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">Du</label>
                    <input type="date" name="du" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">Au</label>
                    <input type="date" name="au" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+13 days')) ?>" required>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Durée (min)</label>
                    <input type="number" name="duree" id="genDuree" class="form-control" min="15" step="15" value="<?= (int) ($services[0]['duree'] ?: 60) ?>" required>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Pause (min)</label>
                    <input type="number" name="pause" class="form-control" min="0" step="5" value="0">
                </div>

            </div>

            <button class="btn btn-gradient mt-3"><i class="bi bi-lightning-charge me-1"></i> Générer les créneaux</button>

            <?php endif; ?>

        </form>

    </div>

    <!-- AJOUT MANUEL -->
    <div class="col-xl-5">

        <form method="POST" class="bp-card h-100">

            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ajouter">

            <h5 class="mb-1"><i class="bi bi-plus-circle text-primary me-1"></i> Créneau ponctuel</h5>
            <p class="text-muted small mb-3">Pour une date exceptionnelle, hors horaires habituels.</p>

            <div class="row g-3">

                <div class="col-12">
                    <select name="service_id" class="form-select" required>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <input type="date" name="jour" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-6"><input type="time" name="debut" class="form-control" required></div>
                <div class="col-6"><input type="time" name="fin" class="form-control" required></div>

            </div>

            <button class="btn btn-primary mt-3">Ajouter</button>

        </form>

    </div>

</div>

<!-- ======================================================
CRÉNEAUX À VENIR
====================================================== -->
<div class="bp-card">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">Créneaux à venir</h5>
        <div class="d-flex gap-3 small">
            <span><span class="badge bg-info">&nbsp;</span> Libre</span>
            <span><span class="badge bg-warning">&nbsp;</span> En attente</span>
            <span><span class="badge bg-success">&nbsp;</span> Confirmé</span>
        </div>
    </div>

    <?php if (empty($parJour)): ?>
        <div class="empty-state"><i class="bi bi-calendar-x"></i>Aucun créneau publié pour l'instant.</div>
    <?php endif; ?>

    <?php foreach ($parJour as $date => $creneaux): ?>

        <div class="py-3 border-bottom">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="text-capitalize"><?= e(jour_court($date)) ?></strong>
                <form method="POST" onsubmit="return confirm('Supprimer tous les créneaux libres de ce jour ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="vider_jour">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <button class="btn btn-link btn-sm text-danger p-0">Vider les créneaux libres</button>
                </form>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($creneaux as $c): ?>
                    <?php $couleur = ['en_attente' => 'warning', 'confirmee' => 'success'][$c['statut']] ?? 'info'; ?>
                    <span class="badge bg-<?= $couleur ?>-subtle text-<?= $couleur ?>-emphasis border border-<?= $couleur ?>-subtle d-inline-flex align-items-center gap-2 py-2 px-3"
                          title="<?= e($c['nom_service']) ?>">
                        <?= heure($c['heure_debut']) ?> - <?= heure($c['heure_fin']) ?>
                        <?php if (count($services) > 1): ?><small class="fw-normal opacity-75"><?= e($c['nom_service']) ?></small><?php endif; ?>
                        <?php if ($c['nb_resa'] == 0): ?>
                            <form method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="btn-close" style="font-size:.55rem" aria-label="Supprimer"></button>
                            </form>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>

        </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<script>
// Durée par défaut = durée du service choisi
document.getElementById("genService")?.addEventListener("change", (e) => {
    document.getElementById("genDuree").value = e.target.selectedOptions[0].dataset.duree;
});
</script>

<?php include("../includes/dash_footer.php"); ?>
