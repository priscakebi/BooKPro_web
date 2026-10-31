<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/reservations.php");

$proId = $_SESSION['user_id'];

// ======================================================
// ACTIONS : confirmer / refuser / annuler
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    [$ok, $message] = changer_statut_pro($pdo, $proId, $_POST['id'] ?? 0, $_POST['action'] ?? '');

    flash($message, $ok ? "success" : "danger");

    // Retour à la page d'origine (agenda, tableau de bord...) si elle est dans l'espace pro
    $retour = safe_next($_POST['retour'] ?? null);

    redirect($retour && str_starts_with($retour, '/pro/') ? $retour : "/pro/rdv.php");
}

// ======================================================
// RÉSERVATIONS DES SERVICES DU PRO
// ======================================================

$stmt = $pdo->prepare("

    SELECT r.id, r.statut, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, r.user_id AS client_id, u.nom AS client, u.email, u.telephone,
           (d.date_disponible >= CURDATE()) AS a_venir
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ?
    ORDER BY a_venir DESC, d.date_disponible, d.heure_debut

");
$stmt->execute([$proId]);
$reservations = $stmt->fetchAll();

$pageTitle = "Réservations";
$active = "reservations";
include("../includes/dash_header.php");

?>

<div class="bp-card">

    <?php if (empty($reservations)): ?>

        <p class="text-muted mb-0">Aucune réservation pour le moment.</p>

    <?php else: ?>

    <table class="table table-modern align-middle mb-0">

        <thead>
            <tr><th>Date</th><th>Créneau</th><th>Service</th><th>Client</th><th>Statut</th><th></th></tr>
        </thead>

        <tbody>

        <?php foreach ($reservations as $r): ?>

            <tr class="<?= $r['a_venir'] ? '' : 'text-muted' ?>">

                <td><?= date_fr($r['date_disponible']) ?></td>

                <td><?= heure($r['heure_debut']) ?> - <?= heure($r['heure_fin']) ?></td>

                <td><?= e($r['nom_service']) ?></td>

                <td>
                    <a href="client.php?id=<?= (int) $r['client_id'] ?>" class="fw-semibold text-decoration-none"><?= e($r['client']) ?></a><br>
                    <small><?= e($r['email']) ?> <?= e($r['telephone']) ?></small>
                </td>

                <td><?= statut_badge($r['statut']) ?></td>

                <td class="text-end text-nowrap">

                    <?php if ($r['a_venir']): ?>

                        <?php
                        $boutons = [];
                        if ($r['statut'] === 'en_attente') {
                            $boutons = ['confirmer' => 'success', 'refuser' => 'outline-danger'];
                        } elseif ($r['statut'] === 'confirmee') {
                            $boutons = ['annuler' => 'outline-danger'];
                        }
                        ?>

                        <?php foreach ($boutons as $action => $style): ?>
                            <form method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="<?= $action ?>">
                                <button class="btn btn-<?= $style ?> btn-sm"><?= ucfirst($action) ?></button>
                            </form>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
