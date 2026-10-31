<?php

// ======================================================
// PARTAGER MA PAGE : lien court, QR code, affichette, bouton
// ======================================================

require_once("../includes/auth.php");

require_login('professionnel');

require("../config/database.php");
require("../includes/partage.php");

$proId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, nom, entreprise, metier, ville, photo, slug FROM users WHERE id = ?");
$stmt->execute([$proId]);
$pro = $stmt->fetch();

// ======================================================
// PERSONNALISATION DU LIEN
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    csrf_check();

    $slug = slugifier($_POST['slug'] ?? '');

    $existe = $pdo->prepare("SELECT 1 FROM users WHERE slug = ? AND id <> ?");
    $existe->execute([$slug, $proId]);

    if (!slug_valide($slug)) {
        flash("Le lien doit faire entre 3 et 60 caractères : lettres, chiffres et tirets.", "danger");
    } elseif ($existe->fetch()) {
        flash("Ce lien est déjà utilisé par un autre professionnel.", "danger");
    } else {
        $pdo->prepare("UPDATE users SET slug = ? WHERE id = ?")->execute([$slug, $proId]);
        flash("Lien mis à jour. Pensez à réimprimer vos supports avec le nouveau QR code.", "warning");
    }

    redirect("/pro/partager.php");
}

$slug = slug_pro($pdo, $pro);
$lien = url_courte($slug);
$lienQr = url_courte($slug, 'qr');
$lienBouton = url_courte($slug, 'bouton');
$nomAffiche = $pro['entreprise'] ?: $pro['nom'];

// ======================================================
// VISITES DES 30 DERNIERS JOURS PAR PROVENANCE
// ======================================================

$stmt = $pdo->prepare("
    SELECT source, SUM(nb) FROM vues_pro
    WHERE pro_id = ? AND jour >= CURDATE() - INTERVAL 30 DAY
    GROUP BY source
");
$stmt->execute([$proId]);
$visites = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalVisites = array_sum($visites);

$codeBouton = '<a href="' . e($lienBouton) . '" target="_blank" rel="noopener" '
    . 'style="display:inline-block;padding:12px 22px;border-radius:12px;background:#5b4ef5;color:#fff;'
    . 'font:600 16px/1.2 system-ui,sans-serif;text-decoration:none">Réserver en ligne</a>';

$pageTitle = "Partager ma page";
$pageSubtitle = "Faites venir vos clients directement sur votre agenda en ligne.";
$active = "partager";
include("../includes/dash_header.php");
?>

<div class="row g-4">

    <!-- ======================================================
    LIEN + QR CODE
    ====================================================== -->
    <div class="col-xl-7">

        <div class="bp-card mb-4">

            <h5 class="mb-3"><i class="bi bi-link-45deg text-primary me-1"></i> Votre lien de réservation</h5>

            <div class="input-group mb-2">
                <input type="text" class="form-control" value="<?= e($lien) ?>" id="lienCourt" readonly>
                <button class="btn btn-primary" type="button" data-copier="#lienCourt"><i class="bi bi-copy me-1"></i> Copier</button>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <button type="button" class="btn btn-soft btn-sm d-none" id="partageNatif"><i class="bi bi-share me-1"></i> Partager…</button>
                <a class="btn btn-soft btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode("Réservez directement en ligne avec $nomAffiche : $lien") ?>"><i class="bi bi-whatsapp me-1"></i> WhatsApp</a>
                <a class="btn btn-soft btn-sm" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($lien) ?>"><i class="bi bi-facebook me-1"></i> Facebook</a>
                <a class="btn btn-soft btn-sm" href="sms:?&body=<?= rawurlencode("Réservez en ligne : $lien") ?>"><i class="bi bi-chat-dots me-1"></i> SMS</a>
                <a class="btn btn-soft btn-sm" href="mailto:?subject=<?= rawurlencode("Prenez rendez-vous avec $nomAffiche") ?>&body=<?= rawurlencode("Bonjour,\n\nVous pouvez désormais réserver directement en ligne :\n$lien\n\nÀ bientôt !") ?>"><i class="bi bi-envelope me-1"></i> Email</a>
                <a class="btn btn-soft btn-sm" target="_blank" href="<?= e($lien) ?>"><i class="bi bi-box-arrow-up-right me-1"></i> Ouvrir</a>
            </div>

            <form method="POST" class="small">
                <?= csrf_field() ?>
                <label class="form-label">Personnaliser le lien</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><?= e(parse_url($lien, PHP_URL_HOST) . BASE_URL) ?>/p/</span>
                    <input type="text" name="slug" class="form-control" value="<?= e($slug) ?>" maxlength="60" pattern="[a-z0-9\-]{3,60}" required>
                    <button class="btn btn-outline-primary">Enregistrer</button>
                </div>
                <div class="form-text">Attention : l'ancien lien et l'ancien QR code ne fonctionneront plus.</div>
            </form>

        </div>

        <div class="bp-card mb-4">

            <h5 class="mb-1"><i class="bi bi-qr-code text-primary me-1"></i> QR code</h5>
            <p class="text-muted small">À coller sur votre camion, vos factures, votre vitrine… Un scan ouvre directement votre agenda.</p>

            <div class="d-flex flex-wrap align-items-center gap-4">

                <div id="qr" class="p-2 bg-white rounded-4 border" style="width: 200px; height: 200px" data-url="<?= e($lienQr) ?>"></div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="qrPng"><i class="bi bi-download me-1"></i> Télécharger (PNG)</button>
                    <button type="button" class="btn btn-soft" id="qrSvg"><i class="bi bi-vector-pen me-1"></i> Télécharger (SVG, imprimeur)</button>
                    <button type="button" class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimer l'affichette</button>
                </div>

            </div>

        </div>

        <div class="bp-card">

            <h5 class="mb-1"><i class="bi bi-code-slash text-primary me-1"></i> Bouton pour votre site web</h5>
            <p class="text-muted small">Copiez ce code dans votre site (Wix, WordPress, Facebook…) pour afficher un bouton de réservation.</p>

            <div class="mb-3"><?= $codeBouton ?></div>

            <div class="input-group">
                <textarea class="form-control font-monospace small" rows="3" id="codeBouton" readonly><?= e($codeBouton) ?></textarea>
                <button class="btn btn-soft" type="button" data-copier="#codeBouton"><i class="bi bi-copy"></i></button>
            </div>

        </div>

    </div>

    <!-- ======================================================
    AFFICHETTE + VISITES
    ====================================================== -->
    <div class="col-xl-5">

        <div class="affiche mb-4" id="affiche">
            <div class="affiche-haut">
                <?= avatar($pro, 72) ?>
                <h3 class="mt-3 mb-1"><?= e($nomAffiche) ?></h3>
                <?php if ($pro['metier']): ?><div class="opacity-75"><?= e($pro['metier']) ?><?= $pro['ville'] ? ' · ' . e(ucfirst(mb_strtolower($pro['ville']))) : '' ?></div><?php endif; ?>
            </div>
            <div class="affiche-qr" id="qrAffiche" data-url="<?= e($lienQr) ?>"></div>
            <p class="affiche-titre">Réservez en ligne<br>24h/24</p>
            <p class="affiche-etapes">Scannez · Choisissez un créneau · C'est réservé</p>
            <p class="affiche-url"><?= e(preg_replace('#^https?://#', '', $lien)) ?></p>
        </div>

        <div class="bp-card">

            <h5 class="mb-3">Visites de votre page <span class="text-muted fw-normal fs-6">· 30 derniers jours</span></h5>

            <div class="stat-value mb-3"><?= (int) $totalVisites ?> <span class="fs-6 text-muted fw-normal">visite<?= $totalVisites > 1 ? 's' : '' ?></span></div>

            <?php foreach (SOURCES_VISITE as $cle => $label): ?>
                <?php $nb = (int) ($visites[$cle] ?? 0); ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span><?= e($label) ?></span><strong><?= $nb ?></strong></div>
                    <div class="progress" style="height: 8px">
                        <div class="progress-bar" style="width: <?= $totalVisites ? round($nb / $totalVisites * 100) : 0 ?>%; background: var(--bp-gradient)"></div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {

    const url = document.getElementById("qr").dataset.url;
    const nomFichier = <?= json_encode('qr-' . $slug) ?>;

    // Correction d'erreur "Q" : le code reste lisible même un peu abîmé
    const qr = qrcode(0, "Q");
    qr.addData(url);
    qr.make();

    const svg = qr.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
    document.getElementById("qr").innerHTML = svg;
    document.getElementById("qrAffiche").innerHTML = svg;

    function telecharger(href, nom) {
        const a = document.createElement("a");
        a.href = href;
        a.download = nom;
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    // SVG vectoriel (idéal pour un imprimeur)
    document.getElementById("qrSvg").addEventListener("click", () => {
        const blob = new Blob([qr.createSvgTag({ cellSize: 10, margin: 4 })], { type: "image/svg+xml" });
        telecharger(URL.createObjectURL(blob), nomFichier + ".svg");
    });

    // PNG haute définition (1024 px)
    document.getElementById("qrPng").addEventListener("click", () => {
        const n = qr.getModuleCount(), marge = 4, taille = 1024;
        const cellule = Math.floor(taille / (n + marge * 2));
        const canvas = document.createElement("canvas");
        canvas.width = canvas.height = cellule * (n + marge * 2);
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#000";
        for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) {
            if (qr.isDark(y, x)) ctx.fillRect((x + marge) * cellule, (y + marge) * cellule, cellule, cellule);
        }
        telecharger(canvas.toDataURL("image/png"), nomFichier + ".png");
    });

    // Boutons "Copier"
    document.querySelectorAll("[data-copier]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const champ = document.querySelector(btn.dataset.copier);
            try { await navigator.clipboard.writeText(champ.value); } catch (e) { champ.select(); document.execCommand("copy"); }
            const avant = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Copié';
            setTimeout(() => btn.innerHTML = avant, 1500);
        });
    });

    // Partage natif (mobile)
    if (navigator.share) {
        const b = document.getElementById("partageNatif");
        b.classList.remove("d-none");
        b.addEventListener("click", () => navigator.share({ title: <?= json_encode($nomAffiche) ?>, text: "Réservez en ligne", url: <?= json_encode($lien) ?> }).catch(() => {}));
    }
});
</script>

<?php include("../includes/dash_footer.php"); ?>
