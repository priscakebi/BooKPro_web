    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/app.js"></script>

<?php if (in_array($_SESSION['role'] ?? '', ['professionnel', 'client'], true)): ?>
    <!-- Application installable + notifications -->
    <script src="<?= BASE_URL ?>/assets/pwa.js"></script>
<?php endif; ?>

<?php if (($_SESSION['role'] ?? '') === 'professionnel'): ?>
    <!-- Alertes en direct des nouvelles demandes -->
    <script src="<?= BASE_URL ?>/assets/pro-live.js"></script>
<?php endif; ?>

</body>
</html>
