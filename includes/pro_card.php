<?php
// ======================================================
// CARTE D'UN PROFESSIONNEL (variable attendue : $pro)
// Ligne issue de rechercher_pros()
// ======================================================
?>
<a href="<?= BASE_URL ?>/user/pro.php?id=<?= (int) $pro['id'] ?>" class="bp-card card-hover pro-card">

    <div class="d-flex align-items-center gap-3 mb-3">

        <?= avatar($pro, 60) ?>

        <div class="min-w-0">
            <h5 class="mb-1 text-truncate"><?= e($pro['entreprise'] ?: $pro['nom']) ?></h5>
            <div class="d-flex flex-wrap gap-1">
                <?php if ($pro['metier']): ?>
                    <span class="tag"><i class="bi <?= METIERS[$pro['metier']] ?? 'bi-briefcase' ?>"></i> <?= e($pro['metier']) ?></span>
                <?php endif; ?>
                <?php if ($pro['ville']): ?>
                    <span class="tag bg-light text-dark"><i class="bi bi-geo-alt"></i> <?= e(ucfirst(mb_strtolower($pro['ville']))) ?></span>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <p class="pro-desc mb-3">
        <?= e($pro['description'] ?: ($pro['nb_services'] . " service" . ($pro['nb_services'] > 1 ? 's' : '') . " proposé" . ($pro['nb_services'] > 1 ? 's' : '') . ".")) ?>
    </p>

    <div class="small mb-1">
        <?php if ($pro['nb_avis'] > 0): ?>
            <?= etoiles($pro['note']) ?> <strong><?= e($pro['note']) ?></strong> <span class="text-muted">(<?= (int) $pro['nb_avis'] ?> avis)</span>
        <?php else: ?>
            <span class="text-muted"><i class="bi bi-stars"></i> Nouveau sur BookPro</span>
        <?php endif; ?>
    </div>

    <div class="pro-footer">

        <div class="price-from">
            À partir de
            <strong><?= prix($pro['prix_min']) ?></strong>
        </div>

        <?php if ($pro['prochaine_dispo']): ?>
            <span class="tag tag-success"><i class="bi bi-lightning-charge-fill"></i> <?= e(libelle_dispo($pro['prochaine_dispo'])) ?></span>
        <?php else: ?>
            <span class="tag bg-light text-muted">Pas de créneau</span>
        <?php endif; ?>

    </div>

</a>
