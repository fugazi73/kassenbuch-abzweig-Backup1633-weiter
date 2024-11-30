<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/column_config.php';

// Prüfe Admin-Berechtigung
if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Excel Import | ' . $site_name;
require_once __DIR__ . '/../../includes/header.php';

// Hole benutzerdefinierte Spalten
$custom_columns = getCustomColumns();

// Initialisiere Variablen
$success_message = '';
$error_message = '';
$excel_preview = [];
$excel_columns = [];
$redirect_to_import = false;

// Hole die Header-Zeile aus der Session oder setze Standard
$header_row = isset($_SESSION['excel_header_row']) ? $_SESSION['excel_header_row'] : 6;

// Verarbeite Formular-Aktionen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datei-Upload
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        // Erstelle temporäres Verzeichnis falls nicht vorhanden
        $temp_dir = __DIR__ . '/../../temp';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }
        
        // Generiere eindeutigen Dateinamen
        $temp_file = $temp_dir . '/' . uniqid('excel_') . '.xlsx';
        
        // Verschiebe die hochgeladene Datei
        if (move_uploaded_file($_FILES['excel_file']['tmp_name'], $temp_file)) {
            $_SESSION['excel_file'] = $temp_file;
            $_SESSION['original_filename'] = $_FILES['excel_file']['name'];
        } else {
            $error_message = "Fehler beim Speichern der Datei";
        }
    }
    
    // Header-Zeile setzen
    if (isset($_POST['set_header_row'])) {
        $header_row = (int)$_POST['header_rows'];
        $_SESSION['excel_header_row'] = $header_row;
    }
    
    // Spaltenzuordnung speichern
    if (isset($_POST['save_mapping'])) {
        $mapping = [];
        foreach ($_POST['mapping'] as $db_field => $excel_column) {
            if (!empty($excel_column)) {
                $mapping[$db_field] = $excel_column;
            }
        }
        
        if (saveColumnMapping($mapping)) {
            $success_message = "Spaltenzuordnung wurde gespeichert";
        } else {
            $error_message = "Fehler beim Speichern der Spaltenzuordnung";
        }
    }
    
    // Import starten
    if (isset($_POST['start_import'])) {
        $redirect_to_import = true;
    }
}

// Hole aktuelle Spaltenzuordnung
$current_mapping = loadColumnMapping() ?? [];

// Lade Excel-Vorschau wenn Datei vorhanden
if (isset($_SESSION['excel_file']) && file_exists($_SESSION['excel_file'])) {
    $excel_preview = getExcelPreview($_SESSION['excel_file']);
    $excel_columns = getExcelColumns($_SESSION['excel_file'], $header_row);
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Excel Import</h5>
        </div>
        <div class="card-body">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Excel Upload -->
            <div class="mb-4">
                <h6>Excel-Datei hochladen</h6>
                <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls" required>
                                <button type="submit" class="btn btn-primary">Hochladen</button>
                            </div>
                        </div>
                        <?php if (!empty($excel_preview)): ?>
                            <div class="col-md-6">
                                <div class="input-group mb-3">
                                    <label class="input-group-text">Überschriftszeile</label>
                                    <input type="number" name="header_rows" class="form-control" 
                                           value="<?php echo $header_row; ?>" min="1" max="20">
                                    <button type="submit" name="set_header_row" class="btn btn-secondary">
                                        Zeile setzen
                                    </button>
                                </div>
                                <div class="form-text">
                                    Wählen Sie die Zeile, die die Spaltenüberschriften enthält
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (!empty($excel_preview)): ?>
                <!-- Excel-Vorschau -->
                <div class="mb-4">
                    <h6>Excel-Vorschau</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Zeile</th>
                                    <?php 
                                    $max_cols = 0;
                                    foreach ($excel_preview as $row) {
                                        $max_cols = max($max_cols, count($row));
                                    }
                                    for ($i = 1; $i <= $max_cols; $i++) {
                                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                                        echo "<th>Spalte $col</th>";
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excel_preview as $row_num => $row): ?>
                                    <tr <?php echo $row_num == $header_row ? 'class="table-primary"' : ''; ?>>
                                        <td><?php echo $row_num; ?></td>
                                        <?php 
                                        for ($i = 1; $i <= $max_cols; $i++) {
                                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                                            echo "<td>" . htmlspecialchars($row[$col] ?? '') . "</td>";
                                        }
                                        ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Spaltenzuordnung -->
                <form action="" method="post" id="mappingForm">
                    <div class="mb-4">
                        <h6>Spalten zuordnen</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kassenbuch-Spalte</th>
                                        <th>Excel-Spalte</th>
                                        <th>Pflichtfeld</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Standard-Spalten -->
                                    <?php foreach ($default_columns as $db_field => $label): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td>
                                                <select name="mapping[<?php echo $db_field; ?>]" class="form-select" required>
                                                    <option value="">Bitte wählen...</option>
                                                    <?php foreach ($excel_columns as $col => $name): ?>
                                                        <option value="<?php echo $col; ?>" 
                                                                <?php echo isset($current_mapping[$db_field]) && $current_mapping[$db_field] === $col ? 'selected' : ''; ?>>
                                                            <?php echo $col . ' - ' . htmlspecialchars($name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Benutzerdefinierte Spalten -->
                                    <?php foreach ($custom_columns as $db_field => $label): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td>
                                                <select name="mapping[<?php echo $db_field; ?>]" class="form-select">
                                                    <option value="">Nicht importieren</option>
                                                    <?php foreach ($excel_columns as $col => $name): ?>
                                                        <option value="<?php echo $col; ?>" 
                                                                <?php echo isset($current_mapping[$db_field]) && $current_mapping[$db_field] === $col ? 'selected' : ''; ?>>
                                                            <?php echo $col . ' - ' . htmlspecialchars($name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <i class="bi bi-dash-circle text-muted"></i>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" name="save_mapping" class="btn btn-primary">
                                <i class="bi bi-save"></i> Spaltenzuordnung speichern
                            </button>
                            <?php if (!empty($current_mapping)): ?>
                                <button type="submit" name="start_import" class="btn btn-success">
                                    <i class="bi bi-file-earmark-excel"></i> Import starten
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatisches Submit beim Datei-Upload
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            this.closest('form').submit();
        });
    }

    // Import-Formular abfangen
    const mappingForm = document.getElementById('mappingForm');
    if (mappingForm) {
        mappingForm.addEventListener('submit', function(e) {
            // Nur für den Import-Button
            if (e.submitter && e.submitter.name === 'start_import') {
                e.preventDefault();
                
                // Import starten
                startImport();
            }
        });
    }
});

// Import-Funktion
async function startImport() {
    try {
        // Import-Button deaktivieren
        const importBtn = document.querySelector('button[name="start_import"]');
        if (importBtn) {
            importBtn.disabled = true;
            importBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importiere...';
        }

        // Import durchführen
        const response = await fetch('process_import.php', {
            method: 'POST'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        // Ergebnis im Modal anzeigen
        const resultDiv = document.getElementById('importResult');
        if (result.success) {
            resultDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }

        // Modal anzeigen
        const modal = new bootstrap.Modal(document.getElementById('importResultModal'));
        modal.show();

        // Bei Erfolg: Seite nach Modal-Schließen neu laden
        if (result.success) {
            document.getElementById('importResultModal').addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            });
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Import: ' + error.message);
    } finally {
        // Import-Button zurücksetzen
        const importBtn = document.querySelector('button[name="start_import"]');
        if (importBtn) {
            importBtn.disabled = false;
            importBtn.innerHTML = '<i class="bi bi-file-earmark-excel"></i> Import starten';
        }
    }
}</script>

<!-- Modal für Import-Ergebnis -->
<div class="modal fade" id="importResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import-Ergebnis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div id="importResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 