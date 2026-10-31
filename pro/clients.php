<?php

// ======================================================
// MES CLIENTS : tous ceux qui ont réservé au moins une fois
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/clients.php");

$proId = $_SESSION['user_id'];

$recherche = trim($_GET['q'] ?? '');
$clients = clients_du_pro($pdo, $proId, $recherche);

// Chiffres globaux (sur tous les clients, pas seulement la recherche)
$tous = $recherche === '' ? $clients : clients_du_pro($pdo, $proId);
$nbFideles = count(array_filter($tous, fn($c) => $c['visites'] >= 3));
$totalEncaisse = array_sum(array_column($tous, 'total'));

$pageTitle = "Mes clients";
$pageSubtitle = "Historique, coordonnées et notes privées de chaque client.";
$active = "clients";
include("../includes/dash_header.php");
?>

<div class="row g-3 mb-4">

    <?php
    $cartes = [
        ['bi-people', '', count($tous), 'Clients'],
        ['bi-award', 'teal', $nbFideles, 'Clients fidèles (3 visites et +)'],
        ['bi-currency-euro', 'pink', prix($totalEncaisse), 'RDV honorés au total'],
    ];
    ?>

    <?php foreach ($cartes as [$icon, $couleur, $valeur, $label]): ?>
        <div class="col-md-4">
            <div class="bp-card stat-card">
                <div class="stat-icon <?= $couleur ?>"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="stat-value"><?= e($valeur) ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<div class="bp-card">

    <form class="mb-3" method="GET">
        <div class="input-icon">
            <i class="bi bi-search"></i>
            <input type="search" name="q" class="form-control" placeholder="Rechercher par nom, email, téléphone ou dans vos notes..." value="<?= e($recherche) ?>">
        </div>
    </form>

    <?php if (empty($clients)): ?>

        <div class="empty-state">
            <i class="bi bi-people"></i>
            <?= $recherche !== '' ? "Aucun client ne correspond à « " . e($recherche) . " »." : "Vos clients apparaîtront ici dès leur première réservation." ?>
        </div>

    <?php else: ?>

    <div class="table-responsive">
    <table class="table table-modern align-middle mb-0">

        <thead>
            <tr><th>Client</th><th>Visites</th><th>Dernière visite</th><th>Prochain RDV</th><th>Total</th><th></th></tr>
        </thead>

        <tbody>

        <?php foreach ($clients as $c): ?>

            <tr style="cursor: pointer" onclick="location.href='client.php?id=<?= (int) $c['id'] ?>'">

                <td>
                    <div class="d-flex align-items-center gap-3">
                        <?= avatar($c, 40) ?>
                        <div class="min-w-0">
                            <strong><?= e($c['nom']) ?></strong> <?= badge_fidelite($c) ?>
                            <div class="small text-muted text-truncate" style="max-width: 320px">
                                <?= $c['note'] ? '<i class="bi bi-sticky me-1"></i>' . e(mb_strimwidth($c['note'], 0, 70, '…')) : e($c['telephone'] ?: $c['email']) ?>
                            </div>
                        </div>
                    </div>
                </td>

                <td>
                    <strong><?= (int) $c['visites'] ?></strong>
                    <?php if ($c['annulations'] > 0): ?>
                        <span class="small text-muted d-block"><?= (int) $c['annulations'] ?> annul.</span>
                    <?php endif; ?>
                </td>

                <td><?= $c['derniere_visite'] ? e(jour_court($c['derniere_visite'])) : '<span class="text-muted">-</span>' ?></td>

                <td>
                    <?php if ($c['prochain']): ?>
                        <span class="tag"><i class="bi bi-calendar-event"></i> <?= e(jour_court(substr($c['prochain'], 0, 10)) . ' ' . heure(substr($c['prochain'], 11))) ?></span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>

                <td class="fw-semibold"><?= prix($c['total']) ?></td>

                <td class="text-end"><a href="client.php?id=<?= (int) $c['id'] ?>" class="btn btn-soft btn-sm">Fiche <i class="bi bi-chevron-right"></i></a></td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>
    </div>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
