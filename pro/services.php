<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

// ======================================================
// SERVICES DU PROFESSIONNEL + CRÉNEAUX LIBRES
// ======================================================

$stmt = $pdo->prepare("

    SELECT s.*,
           (SELECT COUNT(*) FROM disponibilites d
             WHERE d.service_id = s.id AND CONCAT(d.date_disponible, ' ', d.heure_debut) > NOW()
               AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.disponibilite_id = d.id AND r.creneau_actif IS NOT NULL)) AS libres
    FROM services s
    WHERE s.user_id = ?
    ORDER BY s.prix

");

$stmt->execute([$_SESSION['user_id']]);

$services = $stmt->fetchAll();

$pageTitle = "Services & tarifs";
$pageSubtitle = "Ce que vous proposez, et à quel prix. Affiché sur votre page publique.";
$active = "services";
$topAction = '<a href="add-services.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Ajouter un service</a>';
include("../includes/dash_header.php");

?>

<?php if (empty($services)): ?>

    <div class="bp-card empty-state">
        <i class="bi bi-tags"></i>
        <h5 class="text-dark">Aucun service pour l'instant</h5>
        <p>Ajoutez vos prestations avec leur prix et leur durée : c'est ce que les clients réservent.</p>
        <a href="add-services.php" class="btn btn-primary">Ajouter mon premier service</a>
    </div>

<?php else: ?>

<div class="row g-4">

    <?php foreach($services as $service): ?>

        <div class="col-md-6 col-xl-4">

            <div class="bp-card card-hover h-100 d-flex flex-column">

                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h5 class="mb-0"><?= e($service['nom_service']) ?></h5>
                    <span class="service-price text-primary"><?= prix($service['prix']) ?></span>
                </div>

                <p class="text-muted small flex-grow-1"><?= e($service['description']) ?></p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php if ($service['duree']): ?>
                        <span class="tag"><i class="bi bi-clock"></i> <?= (int) $service['duree'] ?> min</span>
                    <?php endif; ?>
                    <span class="tag <?= $service['libres'] ? 'tag-success' : 'bg-light text-muted' ?>">
                        <i class="bi bi-calendar-check"></i> <?= (int) $service['libres'] ?> créneau<?= $service['libres'] > 1 ? 'x' : '' ?> libre<?= $service['libres'] > 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="d-flex gap-2">

                    <a href="edit-service.php?id=<?= (int) $service['id'] ?>" class="btn btn-soft btn-sm">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>

                    <!-- Suppression en POST + CSRF (jamais via un simple lien) -->
                    <form method="POST" action="delete-service.php"
                          onsubmit="return confirm('Supprimer ce service et ses disponibilités ?');">

                        <?= csrf_field() ?>

                        <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">

                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i>
                        </button>

                    </form>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<?php include("../includes/dash_footer.php"); ?>
