<?php

// ======================================================
// SESSION + SECURITE (professionnels uniquement)
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

// ======================================================
// SERVICES DU PROFESSIONNEL
// ======================================================

$stmt = $pdo->prepare("

    SELECT *
    FROM services
    WHERE user_id = ?
    ORDER BY id DESC

");

$stmt->execute([$_SESSION['user_id']]);

$services = $stmt->fetchAll();

$pageTitle = "Mes services";
$active = "services";
include("../includes/dash_header.php");

?>

<div class="card p-4">

    <a href="add-services.php" class="btn btn-primary mb-3 align-self-start">
        <i class="bi bi-plus-lg"></i> Ajouter un service
    </a>

    <?php if (empty($services)): ?>

        <p class="text-muted mb-0">Vous n'avez pas encore de service.</p>

    <?php else: ?>

    <table class="table table-bordered align-middle">

        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Durée</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($services as $service): ?>

            <tr>

                <td><?= e($service['nom_service']) ?></td>

                <td><?= e($service['description']) ?></td>

                <td><?= e($service['prix']) ?> €</td>

                <td><?= $service['duree'] ? e($service['duree']) . ' min' : '-' ?></td>

                <td class="text-nowrap">

                    <a
                        href="edit-service.php?id=<?= (int) $service['id'] ?>"
                        class="btn btn-warning btn-sm">

                        Modifier

                    </a>

                    <!-- Suppression en POST + CSRF (jamais via un simple lien) -->
                    <form
                        method="POST"
                        action="delete-service.php"
                        class="d-inline"
                        onsubmit="return confirm('Supprimer ce service et ses disponibilités ?');">

                        <?= csrf_field() ?>

                        <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">

                        <button type="submit" class="btn btn-danger btn-sm">

                            Supprimer

                        </button>

                    </form>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <?php endif; ?>

</div>

<?php include("../includes/dash_footer.php"); ?>
