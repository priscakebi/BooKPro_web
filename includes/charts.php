<?php
// ======================================================
// GRAPHIQUES EN HTML/CSS (sans bibliothèque)
// - une seule couleur par série (couleur de la marque)
// - infobulle au survol ET au clavier (attributs data-tip-*)
// - chaque graphique a sa vue tableau (accessibilité)
// Rendu stylé par .viz-* dans assets/app.css, infobulles par assets/charts.js
// ======================================================

// Graduation "propre" de l'axe : 0, 50, 100, 150...
function axe_propre($max, $nbGraduations = 4)
{
    if ($max <= 0) {
        return [1, 0.25];
    }

    $brut = $max / $nbGraduations;
    $puissance = 10 ** floor(log10($brut));
    $pas = 10 * $puissance;

    foreach ([1, 2, 2.5, 5] as $m) {
        if ($brut <= $m * $puissance) {
            $pas = $m * $puissance;
            break;
        }
    }

    return [ceil($max / $pas) * $pas, $pas];
}

// 1200 => "1 200", 15000 => "15 k"
function nombre_compact($n)
{
    return abs($n) >= 10000 ? number_format($n / 1000, 0, ',', ' ') . ' k' : number_format($n, 0, ',', ' ');
}

// ======================================================
// COLONNES (évolution ou comparaison, une série)
// $points : [['label' => 'janv.', 'valeur' => 120, 'tip' => 'janv. 2026', 'attenue' => false], ...]
// $format : fonction d'affichage des valeurs
// ======================================================
function chart_colonnes(array $points, callable $format, $titreTableau, $unite = '')
{
    $max = max(array_column($points, 'valeur') ?: [0]);
    [$haut, $pas] = axe_propre($max);

    // Étiquettes directes : seulement le maximum et le dernier point
    $iMax = $max > 0 ? array_search($max, array_column($points, 'valeur')) : null;
    $iDernier = count($points) - 1;

    // Axe X : au plus ~12 étiquettes pour éviter les chevauchements
    $tous = max(1, (int) ceil(count($points) / 12));

    ob_start();
    ?>
    <div class="viz-cols" role="img" aria-label="<?= e($titreTableau) ?>">

        <div class="viz-grid">
            <?php for ($k = (int) round($haut / $pas); $k >= 0; $k--): $v = $k * $pas; ?>
                <div class="viz-gridline" style="bottom: <?= $haut ? $v / $haut * 100 : 0 ?>%"><span><?= e(nombre_compact($v)) ?><?= $v == $haut && $unite ? ' ' . e($unite) : '' ?></span></div>
            <?php endfor; ?>
        </div>

        <div class="viz-plot">
            <?php foreach ($points as $i => $p): ?>
                <div class="viz-col-slot" tabindex="0"
                     data-tip-label="<?= e($p['tip'] ?? $p['label']) ?>"
                     data-tip-value="<?= e($format($p['valeur'])) ?>">
                    <div class="viz-col <?= !empty($p['attenue']) ? 'is-muted' : '' ?>" style="height: <?= $haut ? $p['valeur'] / $haut * 100 : 0 ?>%">
                        <?php if ($p['valeur'] > 0 && ($i === $iMax || $i === $iDernier)): ?>
                            <span class="viz-col-label"><?= e($format($p['valeur'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="viz-x <?= $i % $tous !== 0 && $i !== $iDernier ? 'invisible' : '' ?>"><?= e($p['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php
    return ob_get_clean() . tableau_donnees($titreTableau, array_map(fn($p) => [$p['tip'] ?? $p['label'], $format($p['valeur'])], $points));
}

// ======================================================
// BARRES HORIZONTALES CLASSÉES (comparer des catégories)
// $items : [['label' => ..., 'valeur' => ..., 'detail' => texte infobulle], ...] déjà triés
// ======================================================
function chart_barres(array $items, callable $format, $titreTableau)
{
    $max = max(array_column($items, 'valeur') ?: [0]);

    ob_start();
    ?>
    <div class="viz-bars" role="img" aria-label="<?= e($titreTableau) ?>">
        <?php foreach ($items as $it): ?>
            <div class="viz-bar-row" tabindex="0"
                 data-tip-label="<?= e($it['label']) ?>"
                 data-tip-value="<?= e($format($it['valeur'])) ?>"
                 data-tip-detail="<?= e($it['detail'] ?? '') ?>">
                <div class="viz-bar-label"><?= e($it['label']) ?></div>
                <div class="viz-bar-track">
                    <div class="viz-bar" style="width: calc((100% - 7.5rem) * <?= $max ? round($it['valeur'] / $max, 4) : 0 ?>)"></div>
                    <span class="viz-bar-value"><?= e($format($it['valeur'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean() . tableau_donnees($titreTableau, array_map(fn($it) => [$it['label'], $format($it['valeur'])], $items));
}

// ======================================================
// BARRE SEGMENTÉE (répartition d'un tout, <= 6 parts)
// $parts : [['label', 'valeur', 'couleur', 'icone', 'texte_sombre' => bool], ...]
// ======================================================
function chart_segments(array $parts, $titreTableau)
{
    $total = array_sum(array_column($parts, 'valeur'));
    $parts = array_values(array_filter($parts, fn($p) => $p['valeur'] > 0));

    ob_start();
    ?>
    <div class="viz-seg" role="img" aria-label="<?= e($titreTableau) ?>">
        <?php foreach ($parts as $p): ?>
            <?php $part = $total ? $p['valeur'] / $total * 100 : 0; ?>
            <div class="viz-seg-part" tabindex="0" style="flex: <?= $p['valeur'] ?> 1 0; background: <?= e($p['couleur']) ?>; color: <?= !empty($p['texte_sombre']) ? '#0b0b0b' : '#fff' ?>"
                 data-tip-label="<?= e($p['label']) ?>"
                 data-tip-value="<?= e($p['valeur'] . ' · ' . round($part) . ' %') ?>">
                <?php if ($part >= 12): ?><span><?= round($part) ?> %</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Légende : couleur + icône + libellé (jamais la couleur seule) -->
    <div class="viz-legend">
        <?php foreach ($parts as $p): ?>
            <span><i class="viz-swatch" style="background: <?= e($p['couleur']) ?>"></i><i class="bi <?= e($p['icone']) ?>"></i> <?= e($p['label']) ?></span>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean() . tableau_donnees($titreTableau, array_map(fn($p) => [$p['label'], $p['valeur'] . ' (' . ($total ? round($p['valeur'] / $total * 100) : 0) . ' %)'], $parts));
}

// ======================================================
// JAUGE (un ratio sur 100 %)
// ======================================================
function chart_jauge($pourcent, $libelle)
{
    $pourcent = max(0, min(100, $pourcent));

    return '<div class="viz-meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . round($pourcent) . '" aria-label="' . e($libelle) . '">'
        . '<div class="viz-meter-fill" style="width: ' . $pourcent . '%"></div></div>';
}

// ======================================================
// VUE TABLEAU (repliée) : les mêmes données, sans graphique
// ======================================================
function tableau_donnees($titre, array $lignes)
{
    $html = '<details class="viz-table mt-2"><summary>Voir les données</summary><table class="table table-sm mb-0"><caption class="visually-hidden">' . e($titre) . '</caption><tbody>';

    foreach ($lignes as [$label, $valeur]) {
        $html .= '<tr><td>' . e($label) . '</td><td class="text-end">' . e($valeur) . '</td></tr>';
    }

    return $html . '</tbody></table></details>';
}
