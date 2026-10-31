<?php

// ======================================================
// SESSION + SECURITE (administrateurs uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('admin');

require("../config/database.php");

// ======================================================
// STATISTIQUES GLOBALES
// ======================================================

$roles = $pdo->query("SELECT role, COUNT(*) FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

$nbServices = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();

$statuts = $pdo->query("SELECT statut, COUNT(*) FROM reservations GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);

// ======================================================
// DERNIÈRES RÉSERVATIONS
// ======================================================

$dernieres = $pdo->query("

    SELECT r.statut, r.created_at, d.date_disponible, d.heure_debut,
           s.nom_service, c.nom AS client, p.nom AS pro
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users c ON r.user_id = c.id
    JOIN users p ON s.user_id = p.id
    ORDER BY r.created_at DESC
    LIMIT 10

")->fetchAll();

$pageTitle = "Administration";
$active = "dashboard";
include("../includes/dash_header.php");

?>

<div class="row g-3">

    <?php
    $cartes = [
        ['bi-people', $roles['client'] ?? 0, 'Clients'],
        ['bi-person-badge', $roles['professionnel'] ?? 0, 'Professionnels'],
        ['bi-briefcase', $nbServices, 'Services'],
        ['bi-calendar-check', array_sum($statuts), 'Réservations'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $nb, $label]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="bp-card stat-card">
                <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value" data-count="<?= (int) $nb ?>"><?= (int) $nb ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<div class="bp-card mt-4">

    <h4>Dernières réservations</h4>

    <?php if (empty($dernieres)): ?>

        <p class="text-muted mb-0">Aucune réservation.</p>

    <?php else: ?>

    <table class="table table-modern align-middle mb-0">
        <thead>
            <tr><th>Créneau</th><th>Service</th><th>Client</th><th>Professionnel</th><th>Statut</th></tr>
        </thead>
        <tbody>
        <?php foreach ($dernieres as $r): ?>
            <tr>
                <td><?= date_fr($r['date_disponible']) ?> <?= heure($r['heure_debut']) ?></td>
                <td><?= e($r['nom_service']) ?></td>
                <td><?= e($r['client']) ?></td>
                <td><?= e($r['pro']) ?></td>
                <td><?= statut_badge($r['statut']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
