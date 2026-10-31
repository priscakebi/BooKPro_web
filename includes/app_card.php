<?php
// ======================================================
// CARTE "APPLICATION" : installation + notifications
// Pro et client ont chacun leur application (texte adapté)
// Masquée une fois l'app installée et les notifications activées
// (pilotée par assets/pwa.js)
// ======================================================

$estPro = ($_SESSION['role'] ?? '') === 'professionnel';
?>
<div class="bp-card app-promo mb-4" id="appCard">

    <div class="d-flex flex-wrap align-items-center gap-4">

        <img src="<?= BASE_URL ?>/assets/icons/icon-192.png" alt="" width="64" height="64" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.3)">

        <div class="flex-grow-1">
            <h5 class="mb-1"><?= $estPro ? 'Application BookPro Pro' : "L'application BookPro" ?></h5>
            <p class="text-muted small mb-1">
                <?= $estPro
                    ? "Votre agenda sur l'écran d'accueil, et une alerte à chaque nouvelle demande, avec Confirmer / Refuser en un geste."
                    : "Vos rendez-vous sur l'écran d'accueil, et une alerte dès qu'un professionnel confirme votre demande, plus un rappel la veille." ?>
            </p>
            <small data-push-status class="text-white-50"></small>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-light d-none" data-install-app><i class="bi bi-download me-1"></i> Installer l'app</button>
            <button type="button" class="btn btn-primary" data-push-toggle><i class="bi bi-bell me-1"></i> Activer les notifications</button>
        </div>

    </div>

</div>
