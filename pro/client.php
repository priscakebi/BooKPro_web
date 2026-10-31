<?php

// ======================================================
// FICHE CLIENT : coordonnées, historique, avis, notes privées
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/clients.php");

$proId = $_SESSION['user_id'];
$clientId = (int) ($_GET['id'] ?? 0);

// Uniquement un client qui a réservé chez ce pro
$client = fiche_client($pdo, $proId, $clientId);

if (!$client) {
    flash("Client introuvable.", "warning");
    redirect("/pro/clients.php");
}

// ======================================================
// ENREGISTREMENT DES NOTES PRIVÉES
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $note = trim($_POST['note'] ?? '');

    if (mb_strlen($note) > 5000) {
        flash("Les notes sont limitées à 5000 caractères.", "danger");
    } elseif ($note === '') {
        $pdo->prepare("DELETE FROM notes_clients WHERE pro_id = ? AND client_id = ?")->execute([$proId, $clientId]);
        flash("Notes effacées.");
    } else {
        $pdo->prepare("
            INSERT INTO notes_clients (pro_id, client_id, note) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE note = VALUES(note)
        ")->execute([$proId, $clientId, $note]);
        flash("Notes enregistrées.");
    }

    redirect("/pro/client.php?id=" . $clientId);
}

// ======================================================
// HISTORIQUE COMPLET + AVIS
// ======================================================

$stmt = $pdo->prepare("
    SELECT r.id, r.statut, r.annule_par, r.created_at, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, s.prix, a.note AS avis_note, a.commentaire AS avis_texte,
           (CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()) AS a_venir
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    LEFT JOIN avis a ON a.reservation_id = r.id
    WHERE s.user_id = ? AND r.user_id = ?
    ORDER BY d.date_disponible DESC, d.heure_debut DESC
");
$stmt->execute([$proId, $clientId]);
$historique = $stmt->fetchAll();

$avis = array_filter($historique, fn($h) => $h['avis_note']);

$stmt = $pdo->prepare("SELECT updated_at FROM notes_clients WHERE pro_id = ? AND client_id = ?");
$stmt->execute([$proId, $clientId]);
$noteMaj = $stmt->fetchColumn();

$pageTitle = $client['nom'];
$pageSubtitle = "Client depuis le " . date_fr(min(array_column($historique, 'created_at')));
$active = "clients";
$topAction = '<a href="clients.php" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i> Tous les clients</a>';
include("../includes/dash_header.php");
?>

<div class="row g-4">

    <!-- ======================================================
    COLONNE GAUCHE : identité + notes
    ====================================================== -->
    <div class="col-lg-4">

        <div class="bp-card text-center mb-4">

            <div class="d-flex justify-content-center mb-3"><?= avatar($client, 88) ?></div>

            <h4 class="mb-1"><?= e($client['nom']) ?></h4>
            <div class="mb-3"><?= badge_fidelite($client) ?></div>

            <div class="d-grid gap-2">
                <?php if ($client['telephone']): ?>
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $client['telephone'])) ?>" class="btn btn-primary"><i class="bi bi-telephone me-1"></i> <?= e($client['telephone']) ?></a>
                <?php endif; ?>
                <a href="mailto:<?= e($client['email']) ?>" class="btn btn-soft text-truncate"><i class="bi bi-envelope me-1"></i> <?= e($client['email']) ?></a>
            </div>

        </div>

        <form method="POST" class="bp-card">

            <?= csrf_field() ?>

            <h5 class="mb-1"><i class="bi bi-sticky text-primary me-1"></i> Notes privées</h5>
            <p class="small text-muted mb-3">Visibles uniquement par vous. Ex : code d'accès, matériel installé, préférences...</p>

            <textarea name="note" class="form-control mb-2" rows="7" maxlength="5000"
                      placeholder="Ajoutez une note sur ce client..."><?= e($client['note']) ?></textarea>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><?= $noteMaj ? 'Modifiées le ' . date_fr($noteMaj) : '' ?></small>
                <button class="btn btn-primary btn-sm">Enregistrer</button>
            </div>

        </form>

    </div>

    <!-- ======================================================
    COLONNE DROITE : chiffres + historique
    ====================================================== -->
    <div class="col-lg-8">

        <div class="row g-3 mb-4">

            <?php
            $cartes = [
                ['bi-check2-circle', 'teal', (int) $client['visites'], 'Visites honorées'],
                ['bi-currency-euro', 'pink', prix($client['total']), 'Total'],
                ['bi-x-circle', 'amber', (int) $client['annulations'], 'Annulations du client'],
            ];
            ?>

            <?php foreach ($cartes as [$icon, $couleur, $valeur, $label]): ?>
                <div class="col-sm-4">
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

        <?php if ($avis): ?>
            <div class="bp-card mb-4">
                <h5 class="mb-3">Avis laissé<?= count($avis) > 1 ? 's' : '' ?></h5>
                <?php foreach ($avis as $a): ?>
                    <div class="mb-2">
                        <?= etoiles($a['avis_note']) ?> <small class="text-muted ms-1"><?= e($a['nom_service']) ?> · <?= date_fr($a['date_disponible']) ?></small>
                        <p class="mb-0">« <?= e($a['avis_texte']) ?> »</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bp-card">

            <h5 class="mb-3">Historique</h5>

            <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">

                <thead>
                    <tr><th>Date</th><th>Service</th><th>Prix</th><th>Statut</th></tr>
                </thead>

                <tbody>
                <?php foreach ($historique as $h): ?>
                    <tr class="<?= $h['a_venir'] ? 'fw-semibold' : '' ?>">
                        <td>
                            <a href="agenda.php?semaine=<?= lundi_de($h['date_disponible']) ?>#resa-<?= (int) $h['id'] ?>" class="text-decoration-none">
                                <span class="text-capitalize-first"><?= e(jour_court($h['date_disponible'])) ?></span> <?= date('Y', strtotime($h['date_disponible'])) ?>
                            </a>
                            <div class="small text-muted"><?= heure($h['heure_debut']) ?> - <?= heure($h['heure_fin']) ?></div>
                        </td>
                        <td><?= e($h['nom_service']) ?></td>
                        <td><?= prix($h['prix']) ?></td>
                        <td>
                            <?= statut_badge($h['statut']) ?>
                            <?php if ($h['statut'] === 'annulee' && $h['annule_par']): ?>
                                <small class="text-muted d-block">par <?= $h['annule_par'] === 'client' ? 'le client' : 'vous' ?></small>
                            <?php elseif ($h['a_venir'] && in_array($h['statut'], ['en_attente', 'confirmee'], true)): ?>
                                <small class="text-primary d-block">À venir</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
            </div>

        </div>

    </div>

</div>

<?php include("../includes/dash_footer.php"); ?>
