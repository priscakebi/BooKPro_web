<?php

// ======================================================
// SESSION + SECURITE (clients uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('client');

require("../config/database.php");

$userId = $_SESSION['user_id'];

// ======================================================
// ACTIONS : annuler / laisser un avis
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'annuler') {

        // Uniquement sa réservation, active et à venir
        $update = $pdo->prepare("

            UPDATE reservations r
            JOIN disponibilites d ON r.disponibilite_id = d.id
            SET r.statut = 'annulee'
            WHERE r.id = ? AND r.user_id = ?
              AND r.statut IN ('en_attente', 'confirmee')
              AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()

        ");
        $update->execute([$id, $userId]);

        $update->rowCount()
            ? flash("Réservation annulée.")
            : flash("Cette réservation ne peut plus être annulée.", "danger");

    } elseif ($action === 'avis') {

        $note = (int) ($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? "");

        // Avis possible seulement sur une prestation confirmée et passée
        $stmt = $pdo->prepare("

            SELECT r.id FROM reservations r
            JOIN disponibilites d ON r.disponibilite_id = d.id
            WHERE r.id = ? AND r.user_id = ? AND r.statut = 'confirmee'
              AND d.date_disponible < CURDATE()

        ");
        $stmt->execute([$id, $userId]);

        if (!$stmt->fetch() || $note < 1 || $note > 5 || $commentaire === "") {
            flash("Avis invalide.", "danger");
        } else {
            try {
                $pdo->prepare("INSERT INTO avis (user_id, reservation_id, note, commentaire) VALUES (?, ?, ?, ?)")
                    ->execute([$userId, $id, $note, $commentaire]);
                flash("Merci pour votre avis !");
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    throw $ex;
                }
                flash("Vous avez déjà donné votre avis.", "warning");
            }
        }
    }

    redirect("/user/reservations.php");
}

// ======================================================
// RÉSERVATIONS DU CLIENT
// ======================================================

$stmt = $pdo->prepare("

    SELECT r.id, r.statut, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, s.prix, u.nom AS pro, u.telephone,
           a.note,
           (CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()) AS a_venir
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN avis a ON a.reservation_id = r.id
    WHERE r.user_id = ? AND r.deleted_by_client = 0
    ORDER BY a_venir DESC, d.date_disponible DESC, d.heure_debut

");
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll();

$pageTitle = "Mes réservations";
$active = "reservations";
include("../includes/dash_header.php");

?>

<?php if (empty($reservations)): ?>

    <div class="card p-4">
        <p class="mb-2">Vous n'avez pas encore de réservation.</p>
        <a href="services.php" class="btn btn-primary align-self-start">Trouver un service</a>
    </div>

<?php else: ?>

<div class="card p-4">

    <table class="table align-middle mb-0">

        <thead>
            <tr><th>Date</th><th>Créneau</th><th>Service</th><th>Professionnel</th><th>Statut</th><th></th></tr>
        </thead>

        <tbody>

        <?php foreach ($reservations as $r): ?>

            <tr class="<?= $r['a_venir'] ? '' : 'text-muted' ?>">

                <td><?= date_fr($r['date_disponible']) ?></td>

                <td><?= heure($r['heure_debut']) ?> - <?= heure($r['heure_fin']) ?></td>

                <td><?= e($r['nom_service']) ?> <small>(<?= e($r['prix']) ?> €)</small></td>

                <td>
                    <?= e($r['pro']) ?>
                    <?php if ($r['statut'] === 'confirmee' && $r['telephone']): ?>
                        <br><small><?= e($r['telephone']) ?></small>
                    <?php endif; ?>
                </td>

                <td><?= statut_badge($r['statut']) ?></td>

                <td class="text-end">

                    <?php if ($r['a_venir'] && in_array($r['statut'], ['en_attente', 'confirmee'], true)): ?>

                        <form method="POST" onsubmit="return confirm('Annuler cette réservation ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <input type="hidden" name="action" value="annuler">
                            <button class="btn btn-outline-danger btn-sm">Annuler</button>
                        </form>

                    <?php elseif ($r['note']): ?>

                        <span class="text-warning"><?= str_repeat('★', (int) $r['note']) ?></span>

                    <?php elseif (!$r['a_venir'] && $r['statut'] === 'confirmee' && $r['date_disponible'] < date('Y-m-d')): ?>

                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#avis<?= (int) $r['id'] ?>">
                            Donner mon avis
                        </button>

                    <?php endif; ?>

                </td>

            </tr>

            <?php if (!$r['note'] && $r['statut'] === 'confirmee' && $r['date_disponible'] < date('Y-m-d')): ?>
            <tr class="collapse" id="avis<?= (int) $r['id'] ?>">
                <td colspan="6">
                    <form method="POST" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="action" value="avis">
                        <select name="note" class="form-select w-auto" required>
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <option value="<?= $n ?>"><?= str_repeat('★', $n) ?></option>
                            <?php endfor; ?>
                        </select>
                        <input type="text" name="commentaire" class="form-control" placeholder="Votre commentaire" maxlength="1000" required>
                        <button class="btn btn-primary">Envoyer</button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php endif; ?>

<?php include("../includes/dash_footer.php"); ?>
