<?php
// ======================================================
// PROFIL PUBLIC D'UN PROFESSIONNEL
// ======================================================

require_once("../includes/auth.php");
require("../config/database.php");
require("../includes/pros.php");

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT id, nom, entreprise, metier, ville, adresse, code_postal, telephone, description, photo, created_at
    FROM users WHERE id = ? AND role = 'professionnel'
");
$stmt->execute([$id]);
$pro = $stmt->fetch();

if (!$pro) {
    flash("Ce professionnel n'existe pas.", "warning");
    redirect("/user/services.php");
}

// ======================================================
// SERVICES + NOTE PAR SERVICE
// ======================================================
$stmt = $pdo->prepare("
    SELECT s.*,
           (SELECT COUNT(*) FROM disponibilites d
             WHERE d.service_id = s.id
               AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
               AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)
           ) AS libres
    FROM services s WHERE s.user_id = ? ORDER BY s.prix
");
$stmt->execute([$id]);
$services = $stmt->fetchAll();

// Service sélectionné pour la réservation (par défaut : le premier avec des créneaux)
$serviceId = (int) ($_GET['service'] ?? 0);
$ids = array_map('intval', array_column($services, 'id'));

if (!in_array($serviceId, $ids, true)) {
    $serviceId = 0;
    foreach ($services as $s) {
        if ($s['libres'] > 0) { $serviceId = (int) $s['id']; break; }
    }
}

// ======================================================
// CRÉNEAUX LIBRES DU SERVICE SÉLECTIONNÉ, GROUPÉS PAR JOUR
// ======================================================
$jours = [];

if ($serviceId) {
    $stmt = $pdo->prepare("
        SELECT d.id, d.date_disponible, d.heure_debut, d.heure_fin
        FROM disponibilites d
        WHERE d.service_id = ?
          AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
          AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)
        ORDER BY d.date_disponible, d.heure_debut
        LIMIT 200
    ");
    $stmt->execute([$serviceId]);

    foreach ($stmt->fetchAll() as $c) {
        $jours[$c['date_disponible']][] = $c;
    }
}

// ======================================================
// AVIS
// ======================================================
$stmt = $pdo->prepare("
    SELECT a.note, a.commentaire, a.created_at, c.nom AS client, s.nom_service
    FROM avis a
    JOIN reservations r ON a.reservation_id = r.id
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users c ON a.user_id = c.id
    WHERE s.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$id]);
$avis = $stmt->fetchAll();

$note = $avis ? round(array_sum(array_column($avis, 'note')) / count($avis), 1) : null;

$horaires = horaires_pro($pdo, $id);
$aDesHoraires = (bool) array_filter($horaires);

$estClient = ($_SESSION['role'] ?? '') === 'client';
$connecte = isset($_SESSION['user_id']);

$pageTitle = $pro['entreprise'] ?: $pro['nom'];
include("../includes/public_header.php");
?>

<div class="pro-cover"></div>

<div class="container pb-5">

    <!-- ======================================================
    EN-TÊTE
    ====================================================== -->
    <div class="pro-head d-flex flex-column flex-md-row align-items-md-start gap-4 mb-5">

        <?= avatar($pro, 150) ?>

        <div class="flex-grow-1 pro-head-text">

            <h1 class="mb-1"><?= e($pro['entreprise'] ?: $pro['nom']) ?></h1>

            <?php if ($pro['entreprise']): ?>
                <p class="text-muted mb-2"><?= e($pro['nom']) ?></p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if ($pro['metier']): ?>
                    <span class="tag"><i class="bi <?= METIERS[$pro['metier']] ?? 'bi-briefcase' ?>"></i> <?= e($pro['metier']) ?></span>
                <?php endif; ?>
                <?php if ($pro['ville']): ?>
                    <span class="tag bg-white text-dark border"><i class="bi bi-geo-alt"></i> <?= e(trim($pro['code_postal'] . ' ' . ucfirst(mb_strtolower($pro['ville'])))) ?></span>
                <?php endif; ?>
                <?php if ($note): ?>
                    <span class="small"><?= etoiles($note) ?> <strong><?= e($note) ?></strong> <span class="text-muted">(<?= count($avis) ?> avis)</span></span>
                <?php endif; ?>
            </div>

        </div>

        <div class="pro-head-action d-flex gap-2">
            <?php if ($serviceId): ?>
                <a href="#reserver" class="btn btn-gradient btn-lg"><i class="bi bi-calendar-check me-1"></i> Réserver</a>
            <?php endif; ?>
        </div>

    </div>

    <div class="row g-4">

        <!-- ======================================================
        COLONNE PRINCIPALE
        ====================================================== -->
        <div class="col-lg-8">

            <?php include("../includes/alertes.php"); ?>

            <?php if ($pro['description']): ?>
                <div class="bp-card mb-4 reveal">
                    <h4 class="mb-3">À propos</h4>
                    <p class="mb-0" style="white-space: pre-line"><?= e($pro['description']) ?></p>
                </div>
            <?php endif; ?>

            <!-- SERVICES ET TARIFS -->
            <div class="bp-card mb-4 reveal">

                <h4 class="mb-2">Services & tarifs</h4>

                <?php foreach ($services as $s): ?>
                    <div class="service-row">
                        <div>
                            <strong class="d-block"><?= e($s['nom_service']) ?></strong>
                            <small class="text-muted"><?= e($s['description']) ?></small>
                            <?php if ($s['duree']): ?>
                                <div><span class="tag mt-2"><i class="bi bi-clock"></i> <?= (int) $s['duree'] ?> min</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="service-price"><?= prix($s['prix']) ?></div>
                            <?php if ($s['libres'] > 0): ?>
                                <a href="?id=<?= $id ?>&service=<?= (int) $s['id'] ?>#reserver" class="btn btn-sm <?= (int) $s['id'] === $serviceId ? 'btn-primary' : 'btn-soft' ?> mt-1">Choisir</a>
                            <?php else: ?>
                                <small class="text-muted">Complet</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($services)): ?>
                    <p class="text-muted mb-0">Ce professionnel n'a pas encore publié de service.</p>
                <?php endif; ?>

            </div>

            <!-- RÉSERVATION -->
            <div class="bp-card mb-4 reveal" id="reserver" data-day-scope>

                <h4 class="mb-1">Réserver un créneau</h4>

                <?php if (!$serviceId): ?>

                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        Aucun créneau disponible pour le moment.
                    </div>

                <?php else: ?>

                    <?php $choisi = $services[array_search($serviceId, $ids, true)]; ?>

                    <p class="text-muted mb-4">
                        <?= e($choisi['nom_service']) ?> · <strong class="text-dark"><?= prix($choisi['prix']) ?></strong>
                        <?php if ($choisi['duree']): ?> · <?= (int) $choisi['duree'] ?> min<?php endif; ?>
                    </p>

                    <!-- Jours -->
                    <div class="day-tabs mb-4" data-day-tabs>
                        <?php $i = 0; foreach ($jours as $date => $creneaux): ?>
                            <?php $t = strtotime($date); ?>
                            <button type="button" class="day-tab <?= $i++ === 0 ? 'active' : '' ?>" data-target="jour-<?= $date ?>">
                                <small><?= e(explode(' ', jour_court($date))[0]) ?></small>
                                <strong><?= date('j', $t) ?></strong>
                                <small><?= e(explode(' ', jour_court($date))[2]) ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Heures -->
                    <?php $i = 0; foreach ($jours as $date => $creneaux): ?>
                        <div class="day-panel <?= $i++ === 0 ? 'active' : '' ?>" id="jour-<?= $date ?>">

                            <div class="slot-grid">
                                <?php foreach ($creneaux as $c): ?>

                                    <?php if ($estClient): ?>
                                        <form method="POST" action="prendre_rdv.php"
                                              onsubmit="return confirm('Réserver le <?= e(jour_court($date)) ?> à <?= heure($c['heure_debut']) ?> ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="disponibilite_id" value="<?= (int) $c['id'] ?>">
                                            <button class="slot-btn" title="<?= heure($c['heure_debut']) ?> - <?= heure($c['heure_fin']) ?>"><?= heure($c['heure_debut']) ?></button>
                                        </form>
                                    <?php else: ?>
                                        <a class="slot-btn d-block text-center text-decoration-none"
                                           href="<?= BASE_URL ?>/auth/login.php?next=<?= urlencode('/user/pro.php?id=' . $id . '&service=' . $serviceId . '#reserver') ?>">
                                            <?= heure($c['heure_debut']) ?>
                                        </a>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <?php if (!$estClient): ?>
                        <p class="small text-muted mt-3 mb-0">
                            <i class="bi bi-info-circle"></i>
                            <?= $connecte ? "La réservation est réservée aux comptes clients." : "Connectez-vous ou créez un compte client gratuit pour réserver." ?>
                        </p>
                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <!-- AVIS -->
            <div class="bp-card reveal">

                <h4 class="mb-3">Avis clients <?php if ($note): ?><span class="text-muted fs-6 fw-semibold">· <?= e($note) ?>/5</span><?php endif; ?></h4>

                <?php if (empty($avis)): ?>
                    <p class="text-muted mb-0">Pas encore d'avis. Soyez le premier après votre rendez-vous !</p>
                <?php endif; ?>

                <?php foreach ($avis as $a): ?>
                    <div class="d-flex gap-3 py-3 border-bottom">
                        <?= avatar(['nom' => $a['client']], 42) ?>
                        <div>
                            <strong><?= e(explode(' ', $a['client'])[0]) ?></strong>
                            <span class="small ms-2"><?= etoiles($a['note']) ?></span>
                            <div class="small text-muted mb-1"><?= e($a['nom_service']) ?> · <?= date_fr($a['created_at']) ?></div>
                            <p class="mb-0"><?= e($a['commentaire']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>

        <!-- ======================================================
        COLONNE LATÉRALE
        ====================================================== -->
        <div class="col-lg-4">

            <div class="position-sticky" style="top: 90px;">

                <!-- HORAIRES D'OUVERTURE -->
                <div class="bp-card mb-4 reveal">

                    <h5 class="mb-3"><i class="bi bi-clock me-2 text-primary"></i>Horaires d'ouverture</h5>

                    <?php if ($aDesHoraires): ?>
                        <ul class="list-unstyled hours-list mb-0">
                            <?php foreach (JOURS as $num => $jour): ?>
                                <li class="<?= $num == date('N') ? 'today' : '' ?>">
                                    <span><?= $jour ?></span>
                                    <span><?= $horaires[$num] ? e(implode(', ', $horaires[$num])) : '<span class="text-muted">Fermé</span>' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">Horaires non renseignés.</p>
                    <?php endif; ?>

                </div>

                <!-- COORDONNÉES -->
                <div class="bp-card reveal">

                    <h5 class="mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Coordonnées</h5>

                    <?php if ($pro['adresse'] || $pro['ville']): ?>
                        <p class="mb-3"><?= e($pro['adresse']) ?><br><?= e(trim($pro['code_postal'] . ' ' . $pro['ville'])) ?></p>
                    <?php endif; ?>

                    <?php if ($pro['telephone']): ?>
                        <!-- 1er clic : affiche le numéro ; 2e clic : appelle -->
                        <a class="btn btn-soft w-100"
                           href="tel:<?= e(preg_replace('/[^0-9+]/', '', $pro['telephone'])) ?>"
                           data-phone="<?= e($pro['telephone']) ?>"
                           onclick="if (!this.dataset.shown) { this.dataset.shown = 1; this.textContent = this.dataset.phone; return false; }">
                            <i class="bi bi-telephone me-1"></i> Afficher le numéro
                        </a>
                    <?php endif; ?>

                    <p class="small text-muted mt-3 mb-0">Sur BookPro depuis <?= date('m/Y', strtotime($pro['created_at'])) ?></p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include("../includes/public_footer.php"); ?>
