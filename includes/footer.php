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

<footer class="main-footer mt-auto">
    <div class="container">
        <div class="row align-items-center py-2">
            <div class="col-md-6">
                <div class="footer-brand d-flex align-items-center">
                    <img src="<?= $logo_light ?>" alt="Logo Light" class="footer-logo me-2 theme-light">
                    <img src="<?= $logo_dark ?>" alt="Logo Dark" class="footer-logo me-2 theme-dark">
                    <span class="text-muted"><?= htmlspecialchars($site_name) ?></span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="footer-info d-flex align-items-center justify-content-end">
                    <span class="text-muted me-3">Version 1.0</span>
                    <div class="footer-icons">
                        <a href="#" class="text-muted me-2" title="GitHub">
                            <i class="bi bi-github"></i>
                        </a>
                        <a href="#" class="text-muted me-2" title="Discord">
                            <i class="bi bi-discord"></i>
                        </a>
                        <a href="#" class="text-muted" title="Info">
                            <i class="bi bi-info-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Alle JavaScript-Dateien wurden bereits im Header geladen -->

</body>
</html> 