<footer class="footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <img src="<?= $settings['logo_light'] ?? 'images/logo_light.png' ?>" 
                     alt="Logo" 
                     class="footer-logo">
            </div>
            <div class="col">
                <span class="text-muted">
                    © <?= date('Y') ?> <?= htmlspecialchars($settings['site_name'] ?? COMPANY_NAME) ?>
                </span>
            </div>
            <div class="col-auto">
                <span class="text-muted">Version 1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle mit Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 