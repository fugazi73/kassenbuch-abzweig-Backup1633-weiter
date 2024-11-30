<?php
require_once 'config.php';
require_once 'includes/init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Prüfe Admin-Berechtigung
if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Excel Import | ' . $site_name;
require_once 'includes/header.php';

// Lade Standard-Spalten
$standard_columns = [
    'datum' => ['display_name' => 'Datum', 'type' => 'date', 'required' => true],
    'beleg_nr' => ['display_name' => 'Beleg-Nr.', 'type' => 'text', 'required' => true],
    'bemerkung' => ['display_name' => 'Bemerkung', 'type' => 'text', 'required' => true],
    'einnahme' => ['display_name' => 'Einnahme', 'type' => 'decimal', 'required' => true],
    'ausgabe' => ['display_name' => 'Ausgabe', 'type' => 'decimal', 'required' => true]
];

// Lade gespeicherte Excel-Spalten-Zuordnungen
$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'excel_mapping_%'");
$saved_mappings = [];
while ($row = $result->fetch_assoc()) {
    $column_name = str_replace('excel_mapping_', '', $row['setting_key']);
    $saved_mappings[$column_name] = $row['setting_value'];
}

// Nach den Standard-Spalten laden
// Lade existierende benutzerdefinierte Spalten aus der Datenbank
$custom_columns = [];
$custom_columns_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
if ($row = $custom_columns_result->fetch_assoc()) {
    $custom_columns = json_decode($row['setting_value'], true) ?: [];
    
    // Prüfe ob die Spalten in der Tabelle existieren
    foreach ($custom_columns as $index => $column) {
        $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
        $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
        if ($result->num_rows === 0) {
            // Spalte existiert nicht in der Tabelle - erstelle sie
            $sql_type = '';
            switch($column['type']) {
                case 'text':
                    $sql_type = 'VARCHAR(255)';
                    break;
                case 'date':
                    $sql_type = 'DATE';
                    break;
                case 'decimal':
                    $sql_type = 'DECIMAL(10,2)';
                    break;
                case 'integer':
                    $sql_type = 'INT';
                    break;
                default:
                    $sql_type = 'VARCHAR(255)';
            }
            $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN `$column_name` $sql_type";
            $conn->query($sql);
        }
    }
}

?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Excel-Import</h5>
        </div>
        <div class="card-body">
            <!-- Standard-Spaltenzuordnung -->
            <div class="mb-4">
                <h6>Standard-Spaltenzuordnung</h6>
                <form id="standardMappingForm" class="mb-3">
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
                                <?php
                                $required_columns = [
                                    'datum' => ['name' => 'Datum', 'required' => true],
                                    'beleg_nr' => ['name' => 'Beleg-Nr.', 'required' => false],
                                    'bemerkung' => ['name' => 'Bemerkung', 'required' => true],
                                    'einnahme' => ['name' => 'Einnahme', 'required' => true],
                                    'ausgabe' => ['name' => 'Ausgabe', 'required' => true]
                                ];

                                // Hole gespeicherte Mappings
                                $mappings = [];
                                $mapping_result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'excel_mapping_%'");
                                while ($row = $mapping_result->fetch_assoc()) {
                                    $column = str_replace('excel_mapping_', '', $row['setting_key']);
                                    $mappings[$column] = $row['setting_value'];
                                }

                                foreach ($required_columns as $column => $config):
                                    $current_mapping = $mappings[$column] ?? '';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($config['name']) ?></td>
                                    <td>
                                        <select name="standard_mapping[<?= $column ?>]" class="form-select" <?= $config['required'] ? 'required' : '' ?>>
                                            <option value="">-</option>
                                            <?php for ($i = 0; $i < 26; $i++): ?>
                                                <?php $letter = chr(65 + $i); ?>
                                                <option value="<?= $letter ?>" 
                                                    <?= $current_mapping === $letter ? 'selected' : '' ?>>
                                                    Spalte <?= $letter ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   <?= $config['required'] ? 'checked disabled' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Standard-Zuordnung speichern
                    </button>
                </form>
            </div>

            <!-- Benutzerdefinierte Spalten -->
            <div class="mb-4">
                <h6>Benutzerdefinierte Spalten</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Typ</th>
                                <th>Excel-Spalte</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody id="customColumnsContainer">
                            <?php
                            // Hole alle Spalten aus der kassenbuch_eintraege Tabelle
                            $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege");
                            $all_columns = [];
                            while ($row = $result->fetch_assoc()) {
                                $all_columns[] = $row['Field'];
                            }

                            // Standard-Spalten definieren
                            $standard_columns = ['id', 'datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe', 'saldo', 'kassenstand', 'user_id', 'created_at'];

                            // Benutzerdefinierte Spalten sind alle, die nicht Standard sind
                            $custom_db_columns = array_diff($all_columns, $standard_columns);

                            // Hole die Konfiguration der benutzerdefinierten Spalten
                            $custom_columns_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
                            $custom_columns_config = [];
                            if ($row = $custom_columns_result->fetch_assoc()) {
                                $custom_columns_config = json_decode($row['setting_value'], true) ?: [];
                            }

                            // Zeige alle benutzerdefinierten Spalten an
                            foreach ($custom_db_columns as $column_name): 
                                // Finde die Konfiguration für diese Spalte
                                $config = null;
                                foreach ($custom_columns_config as $conf) {
                                    if (strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $conf['name'])) === $column_name) {
                                        $config = $conf;
                                        break;
                                    }
                                }
                                if (!$config) {
                                    $config = ['name' => $column_name, 'type' => 'text'];
                                }
                            ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" 
                                           name="custom_columns[<?= $column_name ?>][name]" 
                                           value="<?= htmlspecialchars($config['name']) ?>" required>
                                </td>
                                <td>
                                    <select class="form-select" name="custom_columns[<?= $column_name ?>][type]">
                                        <option value="text" <?= $config['type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                        <option value="date" <?= $config['type'] === 'date' ? 'selected' : '' ?>>Datum</option>
                                        <option value="decimal" <?= $config['type'] === 'decimal' ? 'selected' : '' ?>>Dezimal</option>
                                        <option value="integer" <?= $config['type'] === 'integer' ? 'selected' : '' ?>>Ganzzahl</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="mapping[<?= $column_name ?>]" class="form-select">
                                        <option value="">-</option>
                                        <?php for ($i = 0; $i < 26; $i++): ?>
                                            <?php $letter = chr(65 + $i); ?>
                                            <option value="<?= $letter ?>" 
                                                <?= ($mappings[$column_name] ?? '') === $letter ? 'selected' : '' ?>>
                                                Spalte <?= $letter ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-column" 
                                            data-column-name="<?= htmlspecialchars($column_name) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="addColumnBtn">
                        <i class="bi bi-plus-circle"></i> Neue Spalte
                    </button>
                    <button type="button" class="btn btn-primary" id="saveColumnsBtn">
                        <i class="bi bi-save"></i> Spalten speichern
                    </button>
                </div>
            </div>

            <!-- Excel-Upload -->
            <div class="mt-4 pt-3 border-top">
                <h6>Excel-Datei hochladen</h6>
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" class="form-control" name="excel_file" 
                               accept=".xlsx,.xls" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anzahl der Überschriftszeilen</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="headerRows" name="header_rows" 
                                   value="1" min="1" max="10">
                            <button type="button" class="btn btn-outline-secondary" id="detectHeaderRows">
                                <i class="bi bi-magic"></i> Automatisch erkennen
                            </button>
                        </div>
                        <small class="text-muted">
                            Geben Sie an, wie viele Zeilen am Anfang der Excel-Datei übersprungen werden sollen.
                            Die letzte Überschriftszeile wird für die Spaltennamen verwendet.
                        </small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="hasHeader" name="hasHeader" checked>
                        <label class="form-check-label" for="hasHeader">
                            Letzte Überschriftszeile enthält Spaltennamen
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Vorschau laden
                    </button>
                </form>
            </div>

            <!-- Vorschau-Bereich -->
            <div id="previewArea" class="mt-4 d-none">
                <h6>Vorschau der Überschriftszeilen</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <tbody id="headerPreview"></tbody>
                    </table>
                </div>
                <h6>Vorschau der Daten</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead id="previewHeader"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="button" id="importButton" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Daten importieren
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const standardMappingForm = document.getElementById('standardMappingForm');
    const uploadForm = document.getElementById('uploadForm');
    const previewArea = document.getElementById('previewArea');
    const headerRowsInput = document.getElementById('headerRows');
    const hasHeaderCheckbox = document.getElementById('hasHeader');
    let currentFile = null;

    // Event-Listener für Dateiauswahl
    document.querySelector('input[name="excel_file"]').addEventListener('change', function(e) {
        currentFile = e.target.files[0];
        if (currentFile) {
            loadPreview(currentFile);
        }
    });

    // Event-Listener für Änderung der Überschriftszeilen
    headerRowsInput.addEventListener('change', function() {
        if (currentFile) {
            loadPreview(currentFile);
        }
    });

    // Event-Listener für Header-Checkbox
    hasHeaderCheckbox.addEventListener('change', function() {
        if (currentFile) {
            loadPreview(currentFile);
        }
    });

    // Excel-Upload und Vorschau
    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (currentFile) {
            await loadPreview(currentFile);
        }
    });

    // Vorschau laden
    async function loadPreview(file) {
        const formData = new FormData();
        formData.append('excel_file', file);
        formData.append('header_rows', headerRowsInput.value);
        formData.append('has_header', hasHeaderCheckbox.checked);

        try {
            const response = await fetch('preview_excel.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server Response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const responseText = await response.text();
                console.error('Invalid Content-Type:', contentType);
                console.error('Response Text:', responseText);
                throw new Error('Server hat keine JSON-Antwort gesendet');
            }

            const data = await response.json();
            console.log('Preview Response:', data); // Debug-Ausgabe

            if (!data.success) {
                throw new Error(data.message || 'Fehler beim Laden der Vorschau');
            }

            showHeaderPreview(data.preview.header_rows);
            showPreview(data.preview);
            updateMappingSuggestions(data.preview.columns, data.preview.db_columns);
            previewArea.classList.remove('d-none');

        } catch (error) {
            console.error('Error:', error);
            showError('Fehler beim Laden der Vorschau: ' + error.message);
        }
    }

    // Überschriftszeilen-Vorschau anzeigen
    function showHeaderPreview(headerRows) {
        const headerPreview = document.getElementById('headerPreview');
        headerPreview.innerHTML = headerRows.map((row, index) => `
            <tr class="${index === headerRows.length - 1 ? 'table-primary' : ''}">
                <td class="text-muted" style="width: 50px;">Zeile ${index + 1}</td>
                ${row.map(cell => `<td>${cell || ''}</td>`).join('')}
            </tr>
        `).join('');
    }

    // Datenvorschau anzeigen
    function showPreview(preview) {
        const previewHeader = document.getElementById('previewHeader');
        const previewBody = document.getElementById('previewBody');

        // Header erstellen
        previewHeader.innerHTML = `
            <tr>
                ${preview.columns.map(col => `<th>${col || ''}</th>`).join('')}
            </tr>
        `;

        // Erste 5 Zeilen als Vorschau
        previewBody.innerHTML = preview.rows.slice(0, 5).map(row => `
            <tr>
                ${row.map(cell => `<td>${cell || ''}</td>`).join('')}
            </tr>
        `).join('');
    }

    // Mapping-Vorschläge aktualisieren
    function updateMappingSuggestions(columns, dbColumns) {
        const mappingSelects = document.querySelectorAll('select[name^="mapping["]');
        
        mappingSelects.forEach(select => {
            const columnName = select.closest('tr')
                                   .querySelector('td:first-child')
                                   .textContent.toLowerCase();

            // Finde passende Excel-Spalte
            const matchingIndex = dbColumns.findIndex(col => 
                col.toLowerCase() === columnName
            );

            if (matchingIndex !== -1) {
                select.value = String.fromCharCode(65 + matchingIndex);
            }
        });
    }

    // Hilfsfunktionen für Benachrichtigungen
    function showSuccess(message) {
        const alert = createAlert('success', message);
        document.querySelector('.card-body').insertAdjacentElement('afterbegin', alert);
    }

    function showError(message) {
        const alert = createAlert('danger', message);
        document.querySelector('.card-body').insertAdjacentElement('afterbegin', alert);
    }

    function createAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Automatisch ausblenden nach 5 Sekunden
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);

        return alert;
    }

    // Automatische Erkennung der Überschriftszeilen
    document.getElementById('detectHeaderRows').addEventListener('click', async function() {
        if (!currentFile) {
            showError('Bitte wählen Sie zuerst eine Excel-Datei aus.');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', currentFile);
        formData.append('detect_headers', '1');

        try {
            const response = await fetch('detect_headers.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                headerRowsInput.value = data.header_rows;
                await loadPreview(currentFile);
            } else {
                throw new Error(data.message || 'Fehler bei der Erkennung');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Fehler bei der automatischen Erkennung: ' + error.message);
        }
    });

    // Import-Button Event-Handler
    document.getElementById('importButton').addEventListener('click', async function() {
        if (!currentFile) {
            showError('Bitte wählen Sie zuerst eine Excel-Datei aus.');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', currentFile);
        formData.append('header_rows', headerRowsInput.value);
        formData.append('has_header', hasHeaderCheckbox.checked);

        try {
            const response = await fetch('process_import.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Erfolgreicher Import
                const alert = createAlert('success', 'Die Daten wurden erfolgreich importiert!');
                document.querySelector('.card-body').insertBefore(alert, document.querySelector('.card-body').firstChild);
                
                // Optional: Formular zurücksetzen
                uploadForm.reset();
                previewArea.classList.add('d-none');
            } else {
                throw new Error(data.message || 'Fehler beim Import');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Fehler beim Import: ' + error.message);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
