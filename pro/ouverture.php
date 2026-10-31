<?php

// ======================================================
// HORAIRES D'OUVERTURE HEBDOMADAIRES
// Affichés sur la page publique et utilisés pour générer les créneaux
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");

$proId = $_SESSION['user_id'];

// ======================================================
// ENREGISTREMENT (on remplace toute la semaine)
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $plages = [];
    $erreur = null;

    foreach (JOURS as $num => $nomJour) {

        if (empty($_POST['ouvert'][$num])) {
            continue;
        }

        $jourPlages = [];

        foreach ([1, 2] as $p) {
            $debut = $_POST['debut'][$num][$p] ?? '';
            $fin = $_POST['fin'][$num][$p] ?? '';

            if ($debut === '' && $fin === '') {
                continue;
            }

            if (!preg_match('/^\d{2}:\d{2}$/', $debut) || !preg_match('/^\d{2}:\d{2}$/', $fin) || $fin <= $debut) {
                $erreur = "$nomJour : l'heure de fermeture doit être après l'heure d'ouverture.";
                break 2;
            }

            $jourPlages[] = [$debut, $fin];
        }

        if (!$jourPlages) {
            $erreur = "$nomJour est coché ouvert mais n'a pas d'horaires.";
            break;
        }

        if (count($jourPlages) === 2 && $jourPlages[1][0] < $jourPlages[0][1]) {
            $erreur = "$nomJour : la 2e plage doit commencer après la fin de la 1re.";
            break;
        }

        foreach ($jourPlages as [$debut, $fin]) {
            $plages[] = [$num, $debut, $fin];
        }
    }

    if ($erreur) {
        flash($erreur, "danger");
    } else {

        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM horaires_ouverture WHERE user_id = ?")->execute([$proId]);

        $insert = $pdo->prepare("INSERT INTO horaires_ouverture (user_id, jour, heure_ouverture, heure_fermeture) VALUES (?, ?, ?, ?)");
        foreach ($plages as [$num, $debut, $fin]) {
            $insert->execute([$proId, $num, $debut, $fin]);
        }

        $pdo->commit();

        flash("Horaires d'ouverture enregistrés.");
    }

    redirect("/pro/ouverture.php");
}

// ======================================================
// HORAIRES ACTUELS : [jour => [[debut, fin], ...]]
// ======================================================

$stmt = $pdo->prepare("SELECT jour, heure_ouverture, heure_fermeture FROM horaires_ouverture WHERE user_id = ? ORDER BY jour, heure_ouverture");
$stmt->execute([$proId]);

$actuels = [];
foreach ($stmt->fetchAll() as $h) {
    $actuels[$h['jour']][] = [heure($h['heure_ouverture']), heure($h['heure_fermeture'])];
}

$pageTitle = "Horaires d'ouverture";
$pageSubtitle = "Affichés sur votre page et utilisés pour générer vos créneaux en un clic.";
$active = "ouverture";
include("../includes/dash_header.php");
?>

<form method="POST" class="bp-card">

    <?= csrf_field() ?>

    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-soft btn-sm" id="copierLundi">
            <i class="bi bi-copy me-1"></i> Copier le lundi sur les jours ouverts
        </button>
    </div>

    <div class="table-responsive">

    <table class="table table-modern align-middle">

        <thead>
            <tr>
                <th style="width: 170px">Jour</th>
                <th>Matin / plage 1</th>
                <th>Après-midi / plage 2 <span class="text-muted fw-normal text-lowercase">(facultatif)</span></th>
            </tr>
        </thead>

        <tbody>

        <?php foreach (JOURS as $num => $nomJour): ?>

            <?php $p = $actuels[$num] ?? []; ?>

            <tr data-jour="<?= $num ?>">

                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input jour-ouvert" type="checkbox" role="switch" name="ouvert[<?= $num ?>]" value="1" id="ouvert<?= $num ?>" <?= $p ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="ouvert<?= $num ?>"><?= $nomJour ?></label>
                    </div>
                </td>

                <?php foreach ([1, 2] as $i): ?>
                    <td>
                        <div class="d-flex align-items-center gap-2 plage">
                            <input type="time" class="form-control" name="debut[<?= $num ?>][<?= $i ?>]" value="<?= e($p[$i - 1][0] ?? ($i === 1 && !$actuels ? '08:00' : '')) ?>">
                            <span class="text-muted">à</span>
                            <input type="time" class="form-control" name="fin[<?= $num ?>][<?= $i ?>]" value="<?= e($p[$i - 1][1] ?? ($i === 1 && !$actuels ? '12:00' : '')) ?>">
                        </div>
                    </td>
                <?php endforeach; ?>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    </div>

    <button class="btn btn-primary btn-lg mt-2">Enregistrer les horaires</button>

</form>

<script>
// Grise les horaires des jours fermés
document.querySelectorAll(".jour-ouvert").forEach((cb) => {
    const maj = () => cb.closest("tr").querySelectorAll(".plage").forEach((p) => p.style.opacity = cb.checked ? 1 : .35);
    cb.addEventListener("change", maj);
    maj();
});

// Recopie les horaires du lundi sur tous les jours cochés
document.getElementById("copierLundi").addEventListener("click", () => {
    const lundi = [...document.querySelectorAll('tr[data-jour="1"] input[type=time]')].map((i) => i.value);
    document.querySelectorAll("tr[data-jour]").forEach((tr) => {
        if (tr.dataset.jour !== "1" && tr.querySelector(".jour-ouvert").checked) {
            tr.querySelectorAll("input[type=time]").forEach((input, i) => input.value = lundi[i]);
        }
    });
});
</script>

<?php include("../includes/dash_footer.php"); ?>
