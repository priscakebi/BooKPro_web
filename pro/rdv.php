<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../config/mail.php");

$proId = $_SESSION['user_id'];

// ======================================================
// ACTIONS : confirmer / refuser / annuler
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    // Transitions autorisées : statut actuel => nouveau statut
    $transitions = [
        'confirmer' => ['en_attente', 'confirmee'],
        'refuser'   => ['en_attente', 'refusee'],
        'annuler'   => ['confirmee', 'annulee'],
    ];

    $action = $_POST['action'] ?? '';

    if (isset($transitions[$action])) {

        [$avant, $apres] = $transitions[$action];

        // Uniquement une réservation d'un service du pro, à venir
        $stmt = $pdo->prepare("

            SELECT r.id, u.email, s.nom_service, d.date_disponible, d.heure_debut
            FROM reservations r
            JOIN disponibilites d ON r.disponibilite_id = d.id
            JOIN services s ON d.service_id = s.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ? AND s.user_id = ? AND r.statut = ?
              AND d.date_disponible >= CURDATE()

        ");
        $stmt->execute([(int) ($_POST['id'] ?? 0), $proId, $avant]);
        $resa = $stmt->fetch();

        if ($resa) {

            $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?")
                ->execute([$apres, $resa['id']]);

            $libelles = ['confirmee' => 'confirmée', 'refusee' => 'refusée', 'annulee' => 'annulée'];

            sendMail(
                $resa['email'],
                "Votre réservation BookPro",
                "Votre réservation « {$resa['nom_service']} » du " . date_fr($resa['date_disponible'])
                    . " à " . heure($resa['heure_debut']) . " a été " . $libelles[$apres] . "."
            );

            flash("Réservation " . $libelles[$apres] . ".");

        } else {
            flash("Action impossible sur cette réservation.", "danger");
        }
    }

    redirect("/pro/rdv.php");
}

// ======================================================
// RÉSERVATIONS DES SERVICES DU PRO
// ======================================================

$stmt = $pdo->prepare("

    SELECT r.id, r.statut, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, u.nom AS client, u.email, u.telephone,
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

<div class="card p-4">

    <?php if (empty($reservations)): ?>

        <p class="text-muted mb-0">Aucune réservation pour le moment.</p>

    <?php else: ?>

    <table class="table align-middle mb-0">

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
                    <?= e($r['client']) ?><br>
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
