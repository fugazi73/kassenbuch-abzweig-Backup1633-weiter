<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Export | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row my-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">
                        <i class="bi bi-download"></i> 
                        Daten exportieren
                    </h3>
                    
                    <form method="get" action="generate_export.php" class="mt-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="von" class="form-label">Von</label>
                                <input type="date" class="form-control" id="von" name="von" 
                                       value="<?= htmlspecialchars($von) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="bis" class="form-label">Bis</label>
                                <input type="date" class="form-control" id="bis" name="bis" 
                                       value="<?= htmlspecialchars($bis) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="format" class="form-label">Format</label>
                                <select class="form-select" id="format" name="format" required>
                                    <option value="xlsx">Excel (XLSX)</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-download"></i> 
                                Export erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
