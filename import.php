<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin und Chef dürfen importieren
if (!in_array($_SESSION['user_role'], ['admin', 'chef'])) {
    handle_forbidden();
}

$page_title = 'Excel Import | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row my-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">
                        <i class="bi bi-file-earmark-excel"></i> 
                        Excel Import
                    </h3>
                    
                    <form action="process_import.php" method="post" enctype="multipart/form-data" class="mt-4">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Excel-Datei auswählen</label>
                            <input type="file" class="form-control" id="excelFile" name="excelFile" 
                                   accept=".xlsx,.xls,.csv" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hasHeader" name="hasHeader" checked>
                            <label class="form-check-label" for="hasHeader">
                                Erste Zeile enthält Überschriften
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Importieren
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 