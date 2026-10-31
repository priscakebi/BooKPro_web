<?php

// ======================================================
// CONGÉS ET FERMETURES EXCEPTIONNELLES
// Pendant un congé : créneaux libres supprimés, aucune réservation
// possible, fermeture annoncée sur la page publique
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/reservations.php");
require_once("../includes/conges.php");

$proId = $_SESSION['user_id'];

// Réservations actives d'un pro sur une période
function reservations_periode(PDO $pdo, $proId, $du, $au)
{
    $stmt = $pdo->prepare("
        SELECT r.id, r.statut, d.date_disponible, d.heure_debut, s.nom_service, u.nom AS client
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE s.user_id = ? AND r.creneau_actif IS NOT NULL
          AND d.date_disponible BETWEEN ? AND ?
        ORDER BY d.date_disponible, d.heure_debut
    ");
    $stmt->execute([$proId, $du, $au]);

    return $stmt->fetchAll();
}

// ======================================================
// ACTIONS
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {

        $du = $_POST['du'] ?? '';
        $au = $_POST['au'] ?: $du;
        $motif = trim($_POST['motif'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $du) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $au) || $au < $du) {
            flash("Période invalide.", "danger");
        } elseif ($au < date('Y-m-d')) {
            flash("Cette période est déjà passée.", "danger");
        } elseif (mb_strlen($motif) > 255) {
            flash("Motif trop long.", "danger");
        } else {

            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO conges (user_id, date_debut, date_fin, motif) VALUES (?, ?, ?, ?)")
                ->execute([$proId, $du, $au, $motif ?: null]);

            // Créneaux libres de la période : supprimés
            $delete = $pdo->prepare("
                DELETE d FROM disponibilites d
                JOIN services s ON d.service_id = s.id
                WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
                  AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id)
            ");
            $delete->execute([$proId, $du, $au]);
            $supprimes = $delete->rowCount();

            $pdo->commit();

            // Réservations déjà prises : annulées seulement si le pro le demande
            $conflits = reservations_periode($pdo, $proId, max($du, date('Y-m-d')), $au);
            $annulees = 0;

            if (!empty($_POST['annuler_reservations'])) {
                foreach ($conflits as $r) {
                    [$ok] = changer_statut_pro($pdo, $proId, $r['id'], $r['statut'] === 'en_attente' ? 'refuser' : 'annuler');
                    $annulees += $ok ? 1 : 0;
                }
                $conflits = [];
            }

            $message = "Fermeture enregistrée. $supprimes créneau" . ($supprimes > 1 ? 'x' : '') . " libre" . ($supprimes > 1 ? 's' : '') . " retiré" . ($supprimes > 1 ? 's' : '') . ".";

            if ($annulees) {
                $message .= " $annulees réservation" . ($annulees > 1 ? 's' : '') . " annulée" . ($annulees > 1 ? 's' : '') . " (clients prévenus).";
            }

            if ($conflits) {
                $message .= " Attention : " . count($conflits) . " réservation" . (count($conflits) > 1 ? 's sont' : ' est') . " toujours prévue" . (count($conflits) > 1 ? 's' : '') . " pendant cette période.";
            }

            flash($message, $conflits ? "warning" : "success");
        }

    } elseif ($action === 'supprimer') {

        $pdo->prepare("DELETE FROM conges WHERE id = ? AND user_id = ?")->execute([(int) ($_POST['id'] ?? 0), $proId]);

        flash("Fermeture supprimée. Pensez à republier des créneaux sur cette période si besoin.");
    }

    redirect("/pro/conges.php");
}

// ======================================================
// FERMETURES EN COURS ET À VENIR (+ réservations concernées)
// ======================================================

$conges = conges_periode($pdo, $proId, date('Y-m-d'), '9999-12-31');

foreach ($conges as &$c) {
    $c['reservations'] = reservations_periode($pdo, $proId, max($c['date_debut'], date('Y-m-d')), $c['date_fin']);
}
unset($c);

$stmt = $pdo->prepare("SELECT * FROM conges WHERE user_id = ? AND date_fin < CURDATE() ORDER BY date_debut DESC LIMIT 5");
$stmt->execute([$proId]);
$passes = $stmt->fetchAll();

$pageTitle = "Congés & fermetures";
$pageSubtitle = "Pendant une fermeture, plus aucun créneau n'est réservable et vos clients sont informés sur votre page.";
$active = "conges";
include("../includes/dash_header.php");
?>

<div class="row g-4">

    <!-- AJOUT -->
    <div class="col-xl-5">

        <form method="POST" class="bp-card">

            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ajouter">

            <h5 class="mb-3"><i class="bi bi-airplane text-primary me-1"></i> Nouvelle fermeture</h5>

            <div class="row g-3">

                <div class="col-6">
                    <label class="form-label">Du</label>
                    <input type="date" name="du" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-6">
                    <label class="form-label">Au (inclus)</label>
                    <input type="date" name="au" class="form-control" min="<?= date('Y-m-d') ?>">
                    <div class="form-text">Vide = un seul jour</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Motif <span class="text-muted fw-normal">(affiché aux clients, facultatif)</span></label>
                    <input type="text" name="motif" class="form-control" maxlength="255" placeholder="Ex : Congés d'été, formation, jour férié...">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="annuler_reservations" value="1" id="annulerResa">
                        <label class="form-check-label small" for="annulerResa">
                            Annuler aussi les réservations déjà prises sur cette période (les clients sont prévenus par email)
                        </label>
                    </div>
                </div>

            </div>

            <button class="btn btn-primary mt-3">Enregistrer la fermeture</button>

        </form>

    </div>

    <!-- LISTE -->
    <div class="col-xl-7">

        <div class="bp-card">

            <h5 class="mb-3">Fermetures en cours et à venir</h5>

            <?php if (empty($conges)): ?>
                <div class="empty-state py-4"><i class="bi bi-sun"></i>Aucune fermeture prévue.</div>
            <?php endif; ?>

            <?php foreach ($conges as $c): ?>

                <div class="py-3 border-bottom">

                    <div class="d-flex justify-content-between align-items-start gap-3">

                        <div>
                            <strong class="text-capitalize-first"><?= e(libelle_conge($c)) ?></strong>
                            <?php if ($c['date_debut'] <= date('Y-m-d')): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">En cours</span>
                            <?php endif; ?>
                            <?php if ($c['motif']): ?>
                                <div class="small text-muted"><?= e($c['motif']) ?></div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" onsubmit="return confirm('Supprimer cette fermeture ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>

                    </div>

                    <?php if ($c['reservations']): ?>
                        <div class="alert alert-warning small mt-2 mb-0">
                            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= count($c['reservations']) ?> réservation<?= count($c['reservations']) > 1 ? 's' : '' ?> pendant cette fermeture :</strong>
                            <ul class="mb-1 mt-1">
                                <?php foreach ($c['reservations'] as $r): ?>
                                    <li class="text-capitalize-first"><?= e(jour_court($r['date_disponible'])) ?> à <?= heure($r['heure_debut']) ?> · <?= e($r['client']) ?> · <?= e($r['nom_service']) ?> (<?= $r['statut'] === 'en_attente' ? 'en attente' : 'confirmée' ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="agenda.php?semaine=<?= lundi_de($c['reservations'][0]['date_disponible']) ?>" class="fw-semibold">Gérer dans l'agenda →</a>
                        </div>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

            <?php if ($passes): ?>
                <p class="small text-muted mt-3 mb-1">Dernières fermetures passées</p>
                <?php foreach ($passes as $c): ?>
                    <div class="small text-muted"><?= e(libelle_conge($c)) ?><?= $c['motif'] ? ' · ' . e($c['motif']) : '' ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>

</div>

<?php include("../includes/dash_footer.php"); ?>
