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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Excel-Import</h4>
            <p class="text-muted mb-0">Importieren Sie Ihre Kassenbuch-Daten aus einer Excel-Datei</p>
        </div>
        <div>
            <a href="download_template.php" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Excel-Vorlage
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="importForm" method="POST" enctype="multipart/form-data">
                <!-- Spalten-Mapping -->
                <div class="mb-3">
                    <h5>Spalten-Zuordnung</h5>
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
                                <?php foreach ($standard_columns as $key => $config): ?>
                                <tr>
                                    <td><?= htmlspecialchars($config['display_name']) ?></td>
                                    <td>
                                        <select name="mapping[<?= $key ?>]" class="form-select" 
                                                <?= $config['required'] ? 'required' : '' ?>>
                                            <option value="">-</option>
                                            <?php for ($i = 0; $i < 26; $i++): ?>
                                                <?php $letter = chr(65 + $i); ?>
                                                <option value="<?= $letter ?>" 
                                                    <?= ($saved_mappings[$key] ?? '') === $letter ? 'selected' : '' ?>>
                                                    Spalte <?= $letter ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($config['required']): ?>
                                            <i class="bi bi-check-circle text-success"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Benutzerdefinierte Spalten -->
                <div class="mb-4">
                    <h5>Benutzerdefinierte Spalten</h5>
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
                                                    <?= ($saved_mappings[$column_name] ?? '') === $letter ? 'selected' : '' ?>>
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
                        <button type="button" class="btn btn-success mt-3" id="addColumnBtn">
                            <i class="bi bi-plus-circle"></i> Neue Spalte
                        </button>
                        <button type="button" class="btn btn-primary mt-3" id="saveColumnsBtn">
                            <i class="bi bi-save"></i> Spalten speichern
                        </button>
                    </div>
                </div>

                <!-- Excel-Import am Ende -->
                <div class="mt-4 pt-3 border-top">
                    <h5>Excel-Import</h5>
                    <div class="mb-3">
                        <label class="form-label">Excel-Datei auswählen</label>
                        <input type="file" class="form-control" name="excel_file" 
                               accept=".xlsx,.xls" required>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   name="has_header" id="hasHeader" checked>
                            <label class="form-check-label" for="hasHeader">
                                Erste Zeile enthält Überschriften
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Importieren
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Vorschau-Bereich -->
    <div id="previewArea" class="card mt-4 d-none">
        <div class="card-header">
            <h5 class="mb-0">Vorschau</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead id="previewHeader"></thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const previewArea = document.getElementById('previewArea');
    const fileInput = document.querySelector('input[name="excel_file"]');

    // Datei-Upload Handler
    fileInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Zeige Dateiname an
        const fileName = document.createElement('div');
        fileName.className = 'selected-file mt-2 text-muted';
        fileName.textContent = `Ausgewählte Datei: ${file.name}`;
        this.parentNode.appendChild(fileName);

        // Lade Vorschau
        await loadPreview(file);
    });

    // Vorschau laden
    async function loadPreview(file) {
        const formData = new FormData();
        formData.append('excel_file', file);
        formData.append('preview', '1');

        try {
            const response = await fetch('preview_excel.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showPreview(data.preview);
                updateMappingSuggestions(data.columns);
            } else {
                throw new Error(data.message || 'Fehler beim Laden der Vorschau');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Fehler beim Laden der Vorschau: ' + error.message);
        }
    }

    // Vorschau anzeigen
    function showPreview(preview) {
        const previewHeader = document.getElementById('previewHeader');
        const previewBody = document.getElementById('previewBody');

        // Header erstellen
        previewHeader.innerHTML = `
            <tr>
                ${preview.columns.map(col => `<th>${col}</th>`).join('')}
            </tr>
        `;

        // Erste 5 Zeilen als Vorschau
        previewBody.innerHTML = preview.rows.slice(0, 5).map(row => `
            <tr>
                ${row.map(cell => `<td>${cell}</td>`).join('')}
            </tr>
        `).join('');

        // Vorschau anzeigen
        previewArea.classList.remove('d-none');
    }

    // Mapping-Vorschläge aktualisieren
    function updateMappingSuggestions(excelColumns) {
        const mappingSelects = document.querySelectorAll('select[name^="mapping["]');
        
        mappingSelects.forEach(select => {
            const columnName = select.closest('tr')
                                   .querySelector('td:first-child')
                                   .textContent.toLowerCase();

            // Finde passende Excel-Spalte
            const matchingColumn = excelColumns.findIndex(col => 
                col.toLowerCase().includes(columnName)
            );

            if (matchingColumn !== -1) {
                select.value = String.fromCharCode(65 + matchingColumn);
            }
        });
    }

    // Spalten speichern
    document.getElementById('saveColumnsBtn').addEventListener('click', async function() {
        const customColumns = [];
        document.querySelectorAll('#customColumnsContainer tr').forEach(row => {
            customColumns.push({
                name: row.querySelector('input[name*="[name]"]').value,
                type: row.querySelector('select[name*="[type]"]').value
            });
        });

        try {
            const response = await fetch('save_custom_columns.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    columns: customColumns
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Fehler beim Speichern der Spalten');
            }

            showSuccess('Spalten wurden erfolgreich gespeichert');
            // Optional: Seite neu laden um die gespeicherten Spalten anzuzeigen
            // setTimeout(() => location.reload(), 1000);
        } catch (error) {
            console.error('Error:', error);
            showError('Fehler beim Speichern der Spalten: ' + error.message);
        }
    });

    // Import-Formular Handler anpassen
    document.getElementById('importForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importiere...';

        try {
            const formData = new FormData(this);
            const response = await fetch('process_import.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showSuccess(data.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.message || 'Import fehlgeschlagen');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Fehler beim Import: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-upload"></i> Importieren';
        }
    });

    // Hilfsfunktionen für Benachrichtigungen
    function showSuccess(message) {
        const alert = createAlert('success', message);
        importForm.insertAdjacentElement('beforebegin', alert);
    }

    function showError(message) {
        const alert = createAlert('danger', message);
        importForm.insertAdjacentElement('beforebegin', alert);
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

    // Neue Spalte hinzufügen
    document.getElementById('addColumnBtn').addEventListener('click', function() {
        const container = document.getElementById('customColumnsContainer');
        const index = container.children.length;
        
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control" 
                       name="custom_columns[${index}][name]" required>
            </td>
            <td>
                <select class="form-select" name="custom_columns[${index}][type]">
                    <option value="text">Text</option>
                    <option value="date">Datum</option>
                    <option value="decimal">Dezimal</option>
                    <option value="integer">Ganzzahl</option>
                </select>
            </td>
            <td>
                <select name="mapping[new_${index}]" class="form-select">
                    <option value="">-</option>
                    ${Array.from(Array(26)).map((_, i) => 
                        `<option value="${String.fromCharCode(65 + i)}">Spalte ${String.fromCharCode(65 + i)}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm delete-column">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(newRow);
    });

    // Spalte löschen
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.delete-column')) {
            const button = e.target.closest('.delete-column');
            const row = button.closest('tr');
            const columnName = button.dataset.columnName;

            if (confirm('Möchten Sie diese Spalte wirklich löschen?')) {
                try {
                    if (columnName) {
                        const response = await fetch('delete_custom_column.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ columnName: columnName })
                        });

                        if (!response.ok) {
                            throw new Error('Netzwerk-Antwort war nicht ok');
                        }

                        const data = await response.json();
                        if (!data.success) {
                            throw new Error(data.message || 'Fehler beim Löschen der Spalte');
                        }

                        showSuccess('Spalte wurde erfolgreich gelöscht');
                        // Seite nach dem Löschen neu laden
                        setTimeout(() => location.reload(), 1000);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Fehler beim Löschen der Spalte: ' + error.message);
                }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
