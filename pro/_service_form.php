<?php
// ======================================================
// FORMULAIRE SERVICE (ajout + modification)
// Variables : $service (valeurs), $submitLabel
// ======================================================
?>
<form method="POST" class="card p-4" style="max-width: 600px;">

    <?= csrf_field() ?>

    <label class="form-label">Nom du service</label>
    <input
        type="text"
        name="nom_service"
        class="form-control mb-3"
        maxlength="255"
        value="<?= e($service['nom_service'] ?? '') ?>"
        required>

    <label class="form-label">Description</label>
    <textarea
        name="description"
        class="form-control mb-3"
        rows="4"
        required><?= e($service['description'] ?? '') ?></textarea>

    <div class="row">

        <div class="col">
            <label class="form-label">Prix (€)</label>
            <input
                type="number"
                name="prix"
                step="0.01"
                min="0"
                class="form-control mb-3"
                value="<?= e($service['prix'] ?? '') ?>"
                required>
        </div>

        <div class="col">
            <label class="form-label">Durée (minutes)</label>
            <input
                type="number"
                name="duree"
                min="1"
                class="form-control mb-3"
                value="<?= e($service['duree'] ?? '') ?>">
        </div>

    </div>

    <div>
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
        <a href="services.php" class="btn btn-link">Retour</a>
    </div>

</form>
