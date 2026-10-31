<!-- ======================================================
FOOTER
====================================================== -->
<footer class="bp-footer">

    <div class="container">

        <div class="row g-4">

            <div class="col-lg-4">
                <a class="d-flex align-items-center gap-2 text-white fw-bold fs-4 mb-3" href="<?= BASE_URL ?>/index.php">
                    <span class="brand-logo"><i class="bi bi-calendar2-check"></i></span> BookPro
                </a>
                <p>Trouvez un professionnel de confiance près de chez vous, comparez les prix et réservez en quelques clics.</p>
            </div>

            <div class="col-6 col-lg-2 offset-lg-2">
                <h6>Clients</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="<?= BASE_URL ?>/user/services.php">Trouver un pro</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/register.php">Créer un compte</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/login.php">Connexion</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Professionnels</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="<?= BASE_URL ?>/auth/pro-register.php">Rejoindre BookPro</a></li>
                    <li><a href="<?= BASE_URL ?>/pro/pro-dashboard.php">Espace pro</a></li>
                </ul>
            </div>

            <div class="col-lg-2">
                <h6>Métiers</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <?php foreach (array_slice(array_keys(METIERS), 0, 4) as $m): ?>
                        <li><a href="<?= BASE_URL ?>/user/services.php?metier=<?= urlencode($m) ?>"><?= e($m) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <hr class="border-secondary my-4">

        <p class="small mb-0">© <?= date('Y') ?> BookPro - Tous droits réservés</p>

    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/app.js"></script>

</body>
</html>
