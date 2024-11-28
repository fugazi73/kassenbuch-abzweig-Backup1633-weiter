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

<footer class="main-footer">
    <div class="container">
        <div class="row align-items-center py-3">
            <div class="col-md-4">
                <div class="footer-brand">
                    <img src="images/ME.png" alt="ME Logo" class="footer-logo">
                    <span>ME</span>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> ME. Alle Rechte vorbehalten.</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="footer-info">
                    <p class="mb-0">Version 1.0</p>
                    <a href="mailto:support@me.de" class="footer-link">
                        <i class="bi bi-envelope"></i> Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle mit Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (falls benötigt) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Footer Manager -->
<script src="js/footer.js"></script>

<!-- Custom Scripts -->
<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
if (file_exists("js/{$current_page}/index.js")) {
    echo "<script src='js/{$current_page}/index.js'></script>";
}
?>

</body>
</html> 