<?php

// ======================================================
// AGENDA DU PRO : semaine en cours, créneaux et réservations
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/reservations.php");

$proId = $_SESSION['user_id'];

// ======================================================
// SEMAINE AFFICHÉE (?semaine=AAAA-MM-JJ, ramenée au lundi)
// ======================================================

$demande = $_GET['semaine'] ?? '';
$lundi = lundi_de(preg_match('/^\d{4}-\d{2}-\d{2}$/', $demande) && strtotime($demande) ? $demande : date('Y-m-d'));
$dimanche = date('Y-m-d', strtotime("$lundi +6 days"));

$jours = [];
for ($i = 0; $i < 7; $i++) {
    $jours[] = date('Y-m-d', strtotime("$lundi +$i days"));
}

$urlSemaine = fn($date) => '?semaine=' . $date;
$retour = '/pro/agenda.php?semaine=' . $lundi;

// ======================================================
// CRÉNEAUX DE LA SEMAINE + RÉSERVATION ACTIVE ÉVENTUELLE
// ======================================================

$stmt = $pdo->prepare("
    SELECT d.id AS dispo_id, d.date_disponible, d.heure_debut, d.heure_fin,
           s.nom_service, s.prix,
           r.id AS resa_id, r.statut, u.nom AS client, u.telephone, u.email
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    LEFT JOIN reservations r ON r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL
    LEFT JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
    ORDER BY d.date_disponible, d.heure_debut
");
$stmt->execute([$proId, $lundi, $dimanche]);

$evenements = [];
foreach ($stmt->fetchAll() as $ev) {
    $ev['etat'] = $ev['statut'] ?: 'libre';
    $ev['passe'] = strtotime($ev['date_disponible'] . ' ' . $ev['heure_fin']) < time();
    $evenements[$ev['date_disponible']][] = $ev;
}

// Horaires d'ouverture par jour de semaine
$stmt = $pdo->prepare("SELECT jour, heure_ouverture, heure_fermeture FROM horaires_ouverture WHERE user_id = ?");
$stmt->execute([$proId]);
$ouverture = [];
foreach ($stmt->fetchAll() as $h) {
    $ouverture[$h['jour']][] = [$h['heure_ouverture'], $h['heure_fermeture']];
}

// ======================================================
// PLAGE HORAIRE AFFICHÉE (selon ouverture et créneaux)
// ======================================================

$heures = [8 * 60, 19 * 60];
$minutes = fn($t) => (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);

foreach ($ouverture as $plages) {
    foreach ($plages as [$o, $f]) {
        $heures = [min($heures[0], $minutes($o)), max($heures[1], $minutes($f))];
    }
}
foreach ($evenements as $evs) {
    foreach ($evs as $ev) {
        $heures = [min($heures[0], $minutes($ev['heure_debut'])), max($heures[1], $minutes($ev['heure_fin']))];
    }
}

$debutJour = intdiv($heures[0], 60);
$finJour = (int) ceil($heures[1] / 60);
$hauteurHeure = 64;

// Position verticale (px) d'une heure dans la grille
$y = fn($t) => ($minutes($t) - $debutJour * 60) / 60 * $hauteurHeure;

// Demandes à traiter (toutes semaines confondues)
$aTraiterListe = demandes_en_attente($pdo, $proId, 20);

// Chiffres de la semaine
$nbConfirmes = 0;
$caSemaine = 0;
$nbLibres = 0;
foreach ($evenements as $evs) {
    foreach ($evs as $ev) {
        if ($ev['etat'] === 'confirmee') { $nbConfirmes++; $caSemaine += $ev['prix']; }
        if ($ev['etat'] === 'libre' && !$ev['passe']) { $nbLibres++; }
    }
}

$pageTitle = "Agenda";
$pageSubtitle = "Semaine du " . jour_court($lundi) . " au " . jour_court($dimanche);
$active = "agenda";
$topAction = '
<div class="d-flex gap-2 align-items-center">
    <a href="' . $urlSemaine(date('Y-m-d', strtotime("$lundi -7 days"))) . '" class="btn btn-soft" aria-label="Semaine précédente"><i class="bi bi-chevron-left"></i></a>
    <a href="agenda.php" class="btn btn-soft">Aujourd\'hui</a>
    <a href="' . $urlSemaine(date('Y-m-d', strtotime("$lundi +7 days"))) . '" class="btn btn-soft" aria-label="Semaine suivante"><i class="bi bi-chevron-right"></i></a>
    <a href="horaires.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Créneaux</a>
</div>';
include("../includes/dash_header.php");

// Données d'un événement pour la fenêtre de détail (JSON dans un attribut)
function ev_data($ev)
{
    return e(json_encode([
        'etat'    => $ev['etat'],
        'passe'   => $ev['passe'],
        'resa'    => $ev['resa_id'],
        'dispo'   => $ev['dispo_id'],
        'quand'   => jour_court($ev['date_disponible']) . ' · ' . heure($ev['heure_debut']) . ' - ' . heure($ev['heure_fin']),
        'service' => $ev['nom_service'] . ' · ' . prix($ev['prix']),
        'client'  => $ev['client'],
        'tel'     => $ev['telephone'],
        'email'   => $ev['email'],
    ], JSON_UNESCAPED_UNICODE));
}
?>

<!-- ======================================================
CARTE APPLICATION (installation + notifications)
====================================================== -->
<?php include("_app_card.php"); ?>

<!-- ======================================================
À TRAITER
====================================================== -->
<?php if ($aTraiterListe): ?>
<div class="bp-card mb-4" style="border-left: 4px solid #f5a524;">

    <h5 class="mb-3"><i class="bi bi-bell-fill text-warning me-1"></i> <?= count($aTraiterListe) ?> demande<?= count($aTraiterListe) > 1 ? 's' : '' ?> à traiter</h5>

    <?php foreach ($aTraiterListe as $d): ?>
        <div class="d-flex flex-wrap align-items-center gap-3 py-2 border-bottom" id="resa-<?= (int) $d['id'] ?>">
            <div class="flex-grow-1">
                <strong><?= e($d['client']) ?></strong> · <?= e($d['nom_service']) ?>
                <div class="small text-muted text-capitalize"><?= e(jour_court($d['date_disponible'])) ?> · <?= heure($d['heure_debut']) ?> - <?= heure($d['heure_fin']) ?> · <?= prix($d['prix']) ?></div>
            </div>
            <div class="d-flex gap-2">
                <?php foreach (['confirmer' => 'btn-success', 'refuser' => 'btn-outline-danger'] as $action => $style): ?>
                    <form method="POST" action="rdv.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                        <input type="hidden" name="action" value="<?= $action ?>">
                        <input type="hidden" name="retour" value="<?= e($retour) ?>">
                        <button class="btn btn-sm <?= $style ?>"><?= $action === 'confirmer' ? '<i class="bi bi-check-lg"></i> Confirmer' : 'Refuser' ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>
<?php endif; ?>

<!-- ======================================================
CHIFFRES DE LA SEMAINE
====================================================== -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="tag"><i class="bi bi-calendar-check"></i> <?= $nbConfirmes ?> RDV confirmé<?= $nbConfirmes > 1 ? 's' : '' ?></span>
    <span class="tag tag-success"><i class="bi bi-currency-euro"></i> <?= prix($caSemaine) ?></span>
    <span class="tag bg-white text-dark border"><i class="bi bi-calendar-plus"></i> <?= $nbLibres ?> créneau<?= $nbLibres > 1 ? 'x' : '' ?> libre<?= $nbLibres > 1 ? 's' : '' ?></span>
</div>

<!-- ======================================================
VUE SEMAINE (ordinateur / tablette)
====================================================== -->
<div class="bp-card p-0 overflow-hidden d-none d-md-block">

    <div class="agenda" style="--h: <?= $hauteurHeure ?>px">

        <!-- En-têtes -->
        <div class="agenda-head"></div>
        <?php foreach ($jours as $jour): ?>
            <div class="agenda-head <?= $jour === date('Y-m-d') ? 'today' : '' ?>">
                <small><?= e(explode(' ', jour_court($jour))[0]) ?></small>
                <strong><?= date('j', strtotime($jour)) ?></strong>
            </div>
        <?php endforeach; ?>

        <!-- Colonne des heures -->
        <div class="agenda-hours" style="height: <?= ($finJour - $debutJour) * $hauteurHeure ?>px">
            <?php for ($h = $debutJour + 1; $h < $finJour; $h++): ?>
                <span style="top: <?= ($h - $debutJour) * $hauteurHeure ?>px"><?= sprintf('%02d:00', $h) ?></span>
            <?php endfor; ?>
        </div>

        <!-- Jours -->
        <?php foreach ($jours as $jour): ?>

            <?php $num = (int) date('N', strtotime($jour)); ?>

            <div class="agenda-col <?= empty($ouverture[$num]) ? 'closed' : '' ?> <?= $jour === date('Y-m-d') ? 'today' : '' ?>"
                 style="height: <?= ($finJour - $debutJour) * $hauteurHeure ?>px">

                <?php foreach ($ouverture[$num] ?? [] as [$o, $f]): ?>
                    <div class="agenda-open" style="top: <?= $y($o) ?>px; height: <?= $y($f) - $y($o) ?>px"></div>
                <?php endforeach; ?>

                <?php if ($jour === date('Y-m-d') && date('H:i') >= sprintf('%02d:00', $debutJour) && date('H:i') < sprintf('%02d:00', $finJour)): ?>
                    <div class="agenda-now" style="top: <?= $y(date('H:i')) ?>px"></div>
                <?php endif; ?>

                <?php foreach ($evenements[$jour] ?? [] as $ev): ?>
                    <?php $hauteur = max(24, $y($ev['heure_fin']) - $y($ev['heure_debut']) - 2); ?>
                    <button type="button"
                            class="agenda-ev <?= $ev['etat'] ?> <?= $ev['passe'] ? 'passe' : '' ?>"
                            style="top: <?= $y($ev['heure_debut']) + 1 ?>px; height: <?= $hauteur ?>px"
                            data-ev="<?= ev_data($ev) ?>"
                            <?= $ev['resa_id'] ? 'data-resa="' . (int) $ev['resa_id'] . '"' : '' ?>>
                        <strong><?= $ev['client'] ? e($ev['client']) : 'Libre' ?></strong>
                        <?php if ($hauteur > 34): ?>
                            <?= heure($ev['heure_debut']) ?> · <?= e($ev['nom_service']) ?>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<!-- ======================================================
VUE JOUR (mobile)
====================================================== -->
<div class="d-md-none" data-day-scope>

    <div class="day-tabs mb-3" data-day-tabs>
        <?php foreach ($jours as $i => $jour): ?>
            <?php $actif = $jour === date('Y-m-d') || ($i === 0 && !in_array(date('Y-m-d'), $jours, true)); ?>
            <button type="button" class="day-tab <?= $actif ? 'active' : '' ?>" data-target="jour-m-<?= $jour ?>">
                <small><?= e(explode(' ', jour_court($jour))[0]) ?></small>
                <strong><?= date('j', strtotime($jour)) ?></strong>
                <small><?= count($evenements[$jour] ?? []) ?: '·' ?></small>
            </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($jours as $i => $jour): ?>
        <?php $actif = $jour === date('Y-m-d') || ($i === 0 && !in_array(date('Y-m-d'), $jours, true)); ?>
        <div class="day-panel <?= $actif ? 'active' : '' ?>" id="jour-m-<?= $jour ?>">

            <?php if (empty($evenements[$jour])): ?>
                <div class="bp-card empty-state py-4"><i class="bi bi-cup-hot"></i>Rien de prévu ce jour-là.</div>
            <?php endif; ?>

            <?php foreach ($evenements[$jour] ?? [] as $ev): ?>
                <button type="button" class="agenda-day-card <?= $ev['etat'] ?> w-100 text-start border-0 shadow-sm <?= $ev['passe'] ? 'opacity-50' : '' ?>"
                        data-ev="<?= ev_data($ev) ?>">
                    <div class="d-flex justify-content-between">
                        <strong><?= heure($ev['heure_debut']) ?> - <?= heure($ev['heure_fin']) ?></strong>
                        <?= $ev['statut'] ? statut_badge($ev['statut']) : '<span class="badge bg-light text-muted">Libre</span>' ?>
                    </div>
                    <div class="small text-muted"><?= e($ev['nom_service']) ?><?= $ev['client'] ? ' · ' . e($ev['client']) : '' ?></div>
                </button>
            <?php endforeach; ?>

        </div>
    <?php endforeach; ?>

</div>

<!-- ======================================================
FENÊTRE DE DÉTAIL D'UN CRÉNEAU
====================================================== -->
<div class="modal fade" id="evModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: 0;">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="evTitre"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <p class="text-muted mb-2 text-capitalize" id="evQuand"></p>
                <p class="mb-3" id="evService"></p>
                <div id="evClient" class="p-3 rounded-4 mb-3" style="background: var(--bp-bg)"></div>

                <!-- Formulaires d'action, affichés selon l'état -->
                <div class="d-flex flex-wrap gap-2" id="evActions">

                    <?php foreach (['confirmer' => ['btn-success', 'Confirmer'], 'refuser' => ['btn-outline-danger', 'Refuser'], 'annuler' => ['btn-outline-danger', 'Annuler le RDV']] as $action => [$style, $label]): ?>
                        <form method="POST" action="rdv.php" data-action="<?= $action ?>" class="d-none"
                              <?= $action === 'annuler' ? 'onsubmit="return confirm(\'Annuler ce rendez-vous confirmé ? Le client sera prévenu.\');"' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="">
                            <input type="hidden" name="action" value="<?= $action ?>">
                            <input type="hidden" name="retour" value="<?= e($retour) ?>">
                            <button class="btn <?= $style ?>"><?= $label ?></button>
                        </form>
                    <?php endforeach; ?>

                    <form method="POST" action="horaires.php" data-action="supprimer" class="d-none">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="retour" value="<?= e($retour) ?>">
                        <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Supprimer ce créneau libre</button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const modal = new bootstrap.Modal(document.getElementById("evModal"));
    const titres = { libre: "Créneau libre", en_attente: "Demande à confirmer", confirmee: "Rendez-vous confirmé" };

    function ouvrir(ev) {
        document.getElementById("evTitre").textContent = titres[ev.etat] || "Créneau";
        document.getElementById("evQuand").textContent = ev.quand;
        document.getElementById("evService").textContent = ev.service;

        // Coordonnées du client (texte uniquement, jamais de HTML)
        const client = document.getElementById("evClient");
        client.replaceChildren();
        client.classList.toggle("d-none", !ev.client);
        if (ev.client) {
            const nom = document.createElement("strong");
            nom.textContent = ev.client;
            client.append(nom);
            [["tel:", ev.tel, "bi-telephone"], ["mailto:", ev.email, "bi-envelope"]].forEach(([prefixe, val, icone]) => {
                if (!val) return;
                const a = document.createElement("a");
                a.href = prefixe + val;
                a.className = "d-block small mt-1 text-decoration-none";
                a.innerHTML = `<i class="bi ${icone} me-1"></i>`;
                a.append(val);
                client.append(a);
            });
        }

        // Actions possibles selon l'état
        const possibles = ev.passe ? [] : { libre: ["supprimer"], en_attente: ["confirmer", "refuser"], confirmee: ["annuler"] }[ev.etat] || [];
        document.querySelectorAll("#evActions form").forEach((f) => {
            const visible = possibles.includes(f.dataset.action);
            f.classList.toggle("d-none", !visible);
            f.querySelector('input[name="id"]').value = f.dataset.action === "supprimer" ? ev.dispo : ev.resa;
        });

        modal.show();
    }

    document.querySelectorAll("[data-ev]").forEach((el) => el.addEventListener("click", () => ouvrir(JSON.parse(el.dataset.ev))));

    // Arrivée depuis une notification (#resa-ID) : ouvre directement le détail
    const cible = location.hash.match(/^#resa-(\d+)$/);
    const ev = cible && document.querySelector(`.agenda-ev[data-resa="${cible[1]}"]`);
    if (ev && getComputedStyle(ev.closest(".bp-card")).display !== "none") ev.click();
});
</script>

<?php include("../includes/dash_footer.php"); ?>
