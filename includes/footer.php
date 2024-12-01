<?php
// Hole die Einstellungen aus der Datenbank
$showLinks = false;
$settings = ['show_impressum' => '0', 'show_datenschutz' => '0'];

try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('show_impressum', 'show_datenschutz')");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        // Prüfe ob mindestens einer der Links angezeigt werden soll
        $showLinks = ($settings['show_impressum'] === '1' || $settings['show_datenschutz'] === '1');
    }
} catch (Exception $e) {
    error_log("Fehler beim Laden der Footer-Einstellungen: " . $e->getMessage());
}
?>

    </div><!-- Ende .container von main -->
</main><!-- Ende main -->

<footer class="footer mt-auto py-3 bg-body-tertiary border-top">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <img src="<?= $basePath ?>/<?= $savedTheme === 'dark' ? $logo_dark : $logo_light ?>" 
                         alt="Logo" height="24" class="me-2">
                    <span class="text-body-secondary">
                        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?>
                        <span class="ms-2">Version <?= APP_VERSION ?></span>
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end align-items-center gap-3">
                    <a href="<?= $basePath ?>/help/about.php" class="text-body-secondary text-decoration-none">
                        <i class="bi bi-info-circle"></i> Über uns
                    </a>
                    <a href="<?= $basePath ?>/help/manual.php" class="text-body-secondary text-decoration-none">
                        <i class="bi bi-book"></i> Handbuch
                    </a>
                    <?php if ($settings['show_datenschutz'] === '1'): ?>
                        <a href="<?= $basePath ?>/datenschutz.php" class="text-body-secondary text-decoration-none">
                            <i class="bi bi-shield-check"></i> Datenschutz
                        </a>
                    <?php endif; ?>
                    <?php if ($settings['show_impressum'] === '1'): ?>
                        <a href="<?= $basePath ?>/impressum.php" class="text-body-secondary text-decoration-none">
                            <i class="bi bi-file-text"></i> Impressum
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</footer>

</body>
</html> 