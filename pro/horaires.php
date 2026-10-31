<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// Chaque disponibilité = un créneau réservable par un client
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

// Services du pro connecté
$stmt = $pdo->prepare("SELECT id, nom_service FROM services WHERE user_id = ? ORDER BY nom_service");
$stmt->execute([$proId]);
$services = $stmt->fetchAll();

$serviceIds = array_map('intval', array_column($services, 'id'));

// ======================================================
// ACTIONS (ajout / suppression)
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {

        $service_id = (int) ($_POST['service_id'] ?? 0);
        $jour = $_POST['jour'] ?? "";
        $debut = $_POST['debut'] ?? "";
        $fin = $_POST['fin'] ?? "";

        if (!in_array($service_id, $serviceIds, true) || !$jour || !$debut || !$fin) {
            flash("Tous les champs sont obligatoires.", "danger");
        } elseif ($jour . ' ' . $debut < date('Y-m-d H:i')) {
            flash("Le créneau ne peut pas être dans le passé.", "danger");
        } elseif ($fin <= $debut) {
            flash("L'heure de fin doit être après l'heure de début.", "danger");
        } else {

            // Pas de chevauchement avec un autre créneau du même pro
            $overlap = $pdo->prepare("

                SELECT COUNT(*)
                FROM disponibilites d
                JOIN services s ON d.service_id = s.id
                WHERE s.user_id = ?
                  AND d.date_disponible = ?
                  AND d.heure_debut < ?
                  AND d.heure_fin > ?

            ");
            $overlap->execute([$proId, $jour, $fin, $debut]);

            if ($overlap->fetchColumn() > 0) {
                flash("Ce créneau chevauche une disponibilité existante.", "danger");
            } else {
                $pdo->prepare("INSERT INTO disponibilites (service_id, date_disponible, heure_debut, heure_fin) VALUES (?,?,?,?)")
                    ->execute([$service_id, $jour, $debut, $fin]);
                flash("Disponibilité ajoutée.");
            }
        }

    } elseif ($action === 'supprimer') {

        // Uniquement un créneau du pro, sans réservation active
        $delete = $pdo->prepare("

            DELETE d FROM disponibilites d
            JOIN services s ON d.service_id = s.id
            WHERE d.id = ?
              AND s.user_id = ?
              AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id)

        ");
        $delete->execute([(int) ($_POST['id'] ?? 0), $proId]);

        if ($delete->rowCount()) {
            flash("Disponibilité supprimée.");
        } else {
            flash("Impossible de supprimer un créneau qui a déjà été réservé.", "danger");
        }
    }

    redirect("/pro/horaires.php");
}

// ======================================================
// DISPONIBILITÉS À VENIR + ÉTAT DE RÉSERVATION
// ======================================================

$stmt = $pdo->prepare("

    SELECT d.*, s.nom_service,
           (SELECT r.statut FROM reservations r
            WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL) AS statut,
           (SELECT COUNT(*) FROM reservations r WHERE r.disponibilite_id = d.id) AS nb_resa
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    WHERE s.user_id = ? AND d.date_disponible >= CURDATE()
    ORDER BY d.date_disponible, d.heure_debut

");
$stmt->execute([$proId]);
$dispos = $stmt->fetchAll();

$pageTitle = "Disponibilités";
$active = "horaires";
include("../includes/dash_header.php");

?>

<?php if (empty($services)): ?>

    <div class="alert alert-info">
        Ajoutez d'abord un <a href="add-services.php">service</a> pour publier des disponibilités.
    </div>

<?php else: ?>

<div class="card p-4 mb-4">

    <h5>Nouveau créneau</h5>

    <form method="POST" class="row g-2 align-items-end">

        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ajouter">

        <div class="col-md-4">
            <label class="form-label">Service</label>
            <select name="service_id" class="form-select" required>
                <?php foreach($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nom_service']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="jour" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Début</label>
            <input type="time" name="debut" class="form-control" required>
        </div>

        <div class="col-md-2">
            <label class="form-label">Fin</label>
            <input type="time" name="fin" class="form-control" required>
        </div>

        <div class="col-md-1">
            <button class="btn btn-primary w-100">Ajouter</button>
        </div>

    </form>

</div>

<?php endif; ?>

<div class="card p-4">

    <h5>Créneaux à venir</h5>

    <?php if (empty($dispos)): ?>

        <p class="text-muted mb-0">Aucun créneau publié.</p>

    <?php else: ?>

    <table class="table align-middle mb-0">
        <thead>
            <tr><th>Date</th><th>Créneau</th><th>Service</th><th>État</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach($dispos as $d): ?>
            <tr>
                <td><?= date_fr($d['date_disponible']) ?></td>
                <td><?= heure($d['heure_debut']) ?> - <?= heure($d['heure_fin']) ?></td>
                <td><?= e($d['nom_service']) ?></td>
                <td><?= $d['statut'] ? statut_badge($d['statut']) : '<span class="badge bg-info">Libre</span>' ?></td>
                <td class="text-end">
                    <?php if ($d['nb_resa'] == 0): ?>
                        <form method="POST" onsubmit="return confirm('Supprimer ce créneau ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
