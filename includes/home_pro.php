<?php
// ======================================================
// ACCUEIL D'UN PRO CONNECTÉ
// (la recherche de pros est réservée aux clients)
// Variables disponibles : $pdo, $_SESSION
// ======================================================

require_once __DIR__ . "/reservations.php";
require_once __DIR__ . "/partage.php";

$proId = (int) $_SESSION['user_id'];

$aTraiter = demandes_en_attente($pdo, $proId, 5);

// Rendez-vous du jour
$stmt = $pdo->prepare("
    SELECT d.heure_debut, d.heure_fin, s.nom_service, u.nom AS client, r.user_id AS client_id
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users u ON r.user_id = u.id
    WHERE s.user_id = ? AND r.statut = 'confirmee' AND d.date_disponible = CURDATE()
    ORDER BY d.heure_debut
");
$stmt->execute([$proId]);
$aujourdhui = $stmt->fetchAll();

// Semaine : RDV confirmés, chiffre d'affaires, créneaux libres
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(r.statut = 'confirmee'), 0) AS rdv,
           COALESCE(SUM(CASE WHEN r.statut = 'confirmee' THEN s.prix END), 0) AS ca,
           COALESCE(SUM(r.id IS NULL AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()), 0) AS libres
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    LEFT JOIN reservations r ON r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL
    WHERE s.user_id = ? AND d.date_disponible BETWEEN ? AND ?
");
$lundi = lundi_de(date('Y-m-d'));
$stmt->execute([$proId, $lundi, date('Y-m-d', strtotime("$lundi +6 days"))]);
$semaine = $stmt->fetch();

$stmt = $pdo->prepare("SELECT id, nom, entreprise, ville, slug FROM users WHERE id = ?");
$stmt->execute([$proId]);
$moi = $stmt->fetch();
$lien = url_courte(slug_pro($pdo, $moi));
?>

<!-- ======================================================
HERO PRO
====================================================== -->
<section class="hero" style="min-height: auto; padding: 8rem 0 4rem;">

    <div class="container">

        <span class="hero-badge mb-3 animate-in"><i class="bi bi-briefcase-fill"></i> Espace professionnel</span>

        <h1 class="mb-2 animate-in delay-1" style="font-size: clamp(2rem, 4.5vw, 3.4rem)">Bonjour <?= e(explode(' ', $_SESSION['user']['nom'])[0]) ?> 👋</h1>

        <p class="lead mb-4 animate-in delay-2">
            <?php if ($aTraiter): ?>
                Vous avez <strong><?= count($aTraiter) ?> demande<?= count($aTraiter) > 1 ? 's' : '' ?></strong> à traiter
            <?php else: ?>
                Aucune demande en attente
            <?php endif; ?>
            et <strong><?= count($aujourdhui) ?> rendez-vous</strong> aujourd'hui.
        </p>

        <div class="d-flex flex-wrap gap-2 animate-in delay-3">
            <a href="pro/agenda.php" class="btn btn-light btn-lg fw-bold"><i class="bi bi-calendar-week me-1"></i> Mon agenda</a>
            <?php if ($aTraiter): ?>
                <a href="pro/agenda.php" class="btn btn-warning btn-lg fw-bold"><i class="bi bi-bell-fill me-1"></i> Traiter les demandes</a>
            <?php endif; ?>
            <a href="pro/pro-dashboard.php" class="btn btn-outline-light btn-lg">Tableau de bord</a>
        </div>

    </div>

</section>

<section class="section">

    <div class="container">

        <div class="row g-4">

            <!-- AUJOURD'HUI -->
            <div class="col-lg-7 reveal">
                <div class="bp-card h-100">
                    <h4 class="mb-3"><i class="bi bi-sun text-warning me-1"></i> Aujourd'hui</h4>

                    <?php if (!$aujourdhui): ?>
                        <div class="empty-state py-4"><i class="bi bi-cup-hot"></i>Pas de rendez-vous confirmé aujourd'hui.</div>
                    <?php endif; ?>

                    <?php foreach ($aujourdhui as $r): ?>
                        <?php $passe = strtotime(date('Y-m-d') . ' ' . $r['heure_fin']) < time(); ?>
                        <a href="pro/client.php?id=<?= (int) $r['client_id'] ?>" class="d-flex align-items-center gap-3 py-2 border-bottom text-decoration-none text-dark <?= $passe ? 'opacity-50' : '' ?>">
                            <span class="tag"><?= heure($r['heure_debut']) ?></span>
                            <div class="flex-grow-1">
                                <strong><?= e($r['client']) ?></strong>
                                <div class="small text-muted"><?= e($r['nom_service']) ?> · jusqu'à <?= heure($r['heure_fin']) ?></div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CETTE SEMAINE -->
            <div class="col-lg-5 reveal reveal-delay-1">
                <div class="bp-card h-100">
                    <h4 class="mb-3">Cette semaine</h4>
                    <div class="row g-3 text-center">
                        <div class="col-4"><div class="stat-value"><?= (int) $semaine['rdv'] ?></div><div class="stat-label">RDV confirmés</div></div>
                        <div class="col-4"><div class="stat-value"><?= prix($semaine['ca']) ?></div><div class="stat-label">Chiffre d'affaires</div></div>
                        <div class="col-4"><div class="stat-value"><?= (int) $semaine['libres'] ?></div><div class="stat-label">Créneaux libres</div></div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <a href="pro/horaires.php" class="btn btn-soft"><i class="bi bi-calendar-plus me-1"></i> Ouvrir des créneaux</a>
                        <a href="pro/stats.php" class="btn btn-soft"><i class="bi bi-graph-up-arrow me-1"></i> Voir mes statistiques</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ATTIRER DES CLIENTS -->
        <div class="cta-pro mt-5 reveal">
            <div class="row align-items-center g-4 position-relative" style="z-index:1">
                <div class="col-lg-8">
                    <span class="eyebrow text-white opacity-75">Remplissez votre agenda</span>
                    <h2 class="section-title mt-2">Partagez votre page de réservation</h2>
                    <p class="mb-0 opacity-75 fs-5 text-break"><?= e(preg_replace('#^https?://#', '', $lien)) ?></p>
                </div>
                <div class="col-lg-4 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="pro/partager.php" class="btn btn-light btn-lg fw-bold"><i class="bi bi-qr-code me-1"></i> Lien & QR code</a>
                    <a href="<?= e($lien) ?>" class="btn btn-outline-light btn-lg">Voir ma page</a>
                </div>
            </div>
        </div>

    </div>

</section>
