<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}
$page_title = 'Excel Import | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Header-Bereich -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Excel-Import</h5>
                    <p class="text-muted">Importieren Sie Ihre Kassenbuch-Daten aus einer Excel-Datei</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-end">
                    <a href="templates/kassenbuch_vorlage.xlsx" class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Excel-Vorlage herunterladen
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload-Bereich -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="process_excel.php" method="POST" enctype="multipart/form-data" class="dropzone" id="excel-dropzone">
                <div class="dz-message">
                    <div class="text-center">
                        <i class="bi bi-cloud-upload display-4"></i>
                        <h5>Excel-Datei hier ablegen oder klicken zum Auswählen</h5>
                        <p class="text-muted">Unterstützte Formate: .xlsx</p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show mt-3">
            <?= $_SESSION['message']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</div>

<!-- Dropzone.js einbinden -->
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

<script>
Dropzone.options.excelDropzone = {
    acceptedFiles: ".xlsx",
    maxFiles: 1,
    init: function() {
        this.on("success", function(file, response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Fehler beim Import');
                }
            } catch (e) {
                alert('Fehler beim Verarbeiten der Antwort');
            }
        });
    }
};
</script>

<?php require_once 'includes/footer.php'; ?>
