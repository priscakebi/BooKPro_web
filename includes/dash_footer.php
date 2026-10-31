    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/app.js"></script>

<?php if (($_SESSION['role'] ?? '') === 'professionnel'): ?>
    <script src="<?= BASE_URL ?>/assets/pro-app.js"></script>
<?php endif; ?>

</body>
</html>
