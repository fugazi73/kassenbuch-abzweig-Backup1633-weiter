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

<footer class="footer mt-auto">
    <div class="container">
        <a href="<?= $basePath ?>/index.php" class="footer-brand">
            <img src="<?= $basePath ?>/<?= $savedTheme === 'dark' ? $logo_dark : $logo_light ?>" alt="Logo">
            <span><?= htmlspecialchars($site_name) ?></span>
        </a>
        <div class="footer-content">
            <div class="footer-icons">
                <a href="<?= $basePath ?>/help/manual.php" class="text-body-secondary" title="Handbuch">
                    <i class="bi bi-book"></i>
                </a>
                <a href="<?= $basePath ?>/help/changelog.php" class="text-body-secondary" title="Changelog">
                    <i class="bi bi-clock-history"></i>
                </a>
                <a href="<?= $basePath ?>/help/about.php" class="text-body-secondary" title="Über">
                    <i class="bi bi-info-circle"></i>
                </a>
            </div>
            <p class="footer-text">
                &copy; <?= date('Y') ?> <a href="https://meincode.eu" class="text-body-secondary text-decoration-none">meincode.eu</a>
            </p>
        </div>
    </div>
</footer>

</body>
</html> 