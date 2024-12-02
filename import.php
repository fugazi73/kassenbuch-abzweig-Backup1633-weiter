<?php
require_once 'includes/init.php';
require_once 'functions.php';

// Prüfe Berechtigung
if (!is_admin()) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

$page_title = "Excel Import - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-file-earmark-excel text-success"></i> Excel Import
            </h1>

            <!-- Upload-Bereich -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-upload text-primary"></i> Datei hochladen
                </h3>
                <div class="upload-area p-4 border rounded bg-light">
                    <form id="uploadForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Excel-Datei auswählen</label>
                            <input type="file" class="form-control" id="excelFile" name="excel_file" 
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Unterstützte Formate: .xlsx, .xls, .csv</div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hasHeaders" name="has_headers" checked>
                            <label class="form-check-label" for="hasHeaders">
                                Erste Zeile enthält Spaltenüberschriften
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Datei analysieren
                        </button>
                    </form>
                </div>
            </div>

            <!-- Spalten-Zuordnung -->
            <div id="columnMappingSection" class="admin-section mb-4" style="display: none;">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-columns-gap text-info"></i> Spalten zuordnen
                </h3>
                <form id="mappingForm">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Excel-Spalte</th>
                                    <th>Zuordnung</th>
                                    <th>Vorschau</th>
                                </tr>
                            </thead>
                            <tbody id="mappingTableBody">
                                <!-- Wird dynamisch gefüllt -->
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Import starten
                    </button>
                </form>
            </div>

            <!-- Import-Status -->
            <div id="importStatus" class="admin-section" style="display: none;">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-info-circle text-primary"></i> Import-Status
                </h3>
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="importMessages" class="alert alert-info">
                    Warte auf Start des Imports...
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
}

.admin-section h3 {
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
}

.upload-area {
    background-color: var(--bs-light);
}

[data-bs-theme="dark"] .upload-area {
    background-color: rgba(255, 255, 255, 0.05);
}

.table th {
    font-size: 0.85rem;
    font-weight: 500;
}

.table td {
    font-size: 0.9rem;
}

.progress {
    background-color: rgba(0,0,0,.05);
}

[data-bs-theme="dark"] .progress {
    background-color: rgba(255,255,255,.05);
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const columnMappingSection = document.getElementById('columnMappingSection');
    const mappingForm = document.getElementById('mappingForm');
    const importStatus = document.getElementById('importStatus');
    const progressBar = document.querySelector('.progress-bar');
    const importMessages = document.getElementById('importMessages');

    // Upload-Formular
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const fileInput = document.getElementById('excelFile');
        const hasHeaders = document.getElementById('hasHeaders').checked;
        
        if (!fileInput.files[0]) {
            alert('Bitte wählen Sie eine Datei aus.');
            return;
        }
        
        formData.append('excel_file', fileInput.files[0]);
        formData.append('has_headers', hasHeaders);
        
        try {
            const response = await fetch('modules/import/detect_headers.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Zeige Spalten-Mapping
                columnMappingSection.style.display = 'block';
                // TODO: Fülle Mapping-Tabelle
            } else {
                alert(result.message || 'Fehler beim Analysieren der Datei');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Hochladen der Datei');
        }
    });

    // Mapping-Formular
    mappingForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const mapping = {};
        // TODO: Sammle Mapping-Daten
        
        try {
            const response = await fetch('modules/import/process_import.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(mapping)
            });
            
            const result = await response.json();
            
            if (result.success) {
                importStatus.style.display = 'block';
                // TODO: Zeige Import-Fortschritt
            } else {
                alert(result.message || 'Fehler beim Import');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Import');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 