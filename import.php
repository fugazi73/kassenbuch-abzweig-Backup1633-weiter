<?php
session_start();
require_once 'config.php';
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Nur Admin und Chef dürfen importieren
if (!in_array($_SESSION['user_role'], ['admin', 'chef'])) {
    handle_forbidden();
}

$page_title = 'Excel Import | Kassenbuch';
require_once 'includes/header.php';

// Hole gespeicherte Spaltenkonfigurationen
$saved_configs = [];
$result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'excel_mapping_%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $saved_configs[] = json_decode($row['config_value'], true);
    }
}
?>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Linke Spalte: Import-Formular -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-file-earmark-excel text-primary me-2"></i>
                    <span class="fw-bold">Excel Import</span>
                </div>
                <div class="card-body">
                    <form id="importForm" action="modules/import/process_import.php" method="post" enctype="multipart/form-data">
                        <!-- Excel-Datei Upload -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Excel-Datei auswählen</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="excelFile" name="excel_file" 
                                       accept=".xlsx,.xls,.csv" required>
                                <button class="btn btn-outline-primary" type="button" id="previewBtn">
                                    <i class="bi bi-eye me-1"></i>Vorschau
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Unterstützte Formate: Excel (.xlsx, .xls) und CSV (.csv)
                            </div>
                        </div>

                        <!-- Import-Konfiguration -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Import-Konfiguration</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <select class="form-select" id="configSelect" name="config_id">
                                        <option value="">Neue Konfiguration</option>
                                        <?php foreach ($saved_configs as $config): ?>
                                        <option value="<?= htmlspecialchars($config['id']) ?>">
                                            <?= htmlspecialchars($config['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="configName" name="config_name" 
                                           placeholder="Name für neue Konfiguration">
                                </div>
                            </div>
                        </div>

                        <!-- Überschriften-Einstellungen -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Überschriften-Einstellungen</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="hasHeader" name="has_header" checked>
                                        <label class="form-check-label" for="hasHeader">
                                            Erste Zeile enthält Überschriften
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="headerRow" name="header_row" 
                                           value="1" min="1" placeholder="Überschriften-Zeile">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i>Import starten
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Zurücksetzen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rechte Spalte: Spalten-Zuordnung -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-grid-3x3-gap text-primary me-2"></i>
                    <span class="fw-bold">Spalten-Zuordnung</span>
                </div>
                <div class="card-body">
                    <div id="columnMappingContainer">
                        <!-- Wird dynamisch durch JavaScript gefüllt -->
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-arrow-left-circle display-4 d-block mb-3"></i>
                            <p>Bitte wählen Sie eine Excel-Datei aus, um die Spalten zuzuordnen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vorschau-Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">
                        <i class="bi bi-table text-primary me-2"></i>
                        Daten-Vorschau
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" id="previewTable">
                            <thead class="table-light"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Basis-Variablen */
:root {
    --primary-color: #0d6efd;
    --primary-hover: #0b5ed7;
    --border-radius: 0.375rem;
}

/* Karten-Design */
.card {
    border-radius: var(--border-radius);
    border: 1px solid rgba(0,0,0,.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid rgba(0,0,0,.125);
    padding: 0.75rem 1rem;
}

/* Form-Elemente */
.form-control, .form-select {
    border-radius: var(--border-radius);
    border: 1px solid rgba(0,0,0,.2);
    padding: 0.5rem 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

.input-group .btn {
    padding: 0.5rem 1rem;
}

/* Mapping-Items */
.column-mapping-item {
    background-color: rgba(0,0,0,.02);
    border: 1px solid rgba(0,0,0,.125);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease-in-out;
}

.column-mapping-item:hover {
    background-color: rgba(0,0,0,.03);
    border-color: var(--primary-color);
}

/* Dark Mode Anpassungen */
[data-bs-theme="dark"] {
    --primary-color: #0d6efd;
    --primary-hover: #0b5ed7;
}

[data-bs-theme="dark"] .card {
    background-color: #2b3035;
    border-color: rgba(255,255,255,.125);
}

[data-bs-theme="dark"] .card-header {
    background-color: #2b3035;
    border-color: rgba(255,255,255,.125);
}

[data-bs-theme="dark"] .column-mapping-item {
    background-color: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.125);
}

[data-bs-theme="dark"] .column-mapping-item:hover {
    background-color: rgba(255,255,255,.1);
    border-color: var(--primary-color);
}

[data-bs-theme="dark"] .table {
    --bs-table-color: #dee2e6;
    --bs-table-bg: transparent;
    --bs-table-border-color: rgba(255,255,255,.125);
}

/* Responsive Anpassungen */
@media (max-width: 992px) {
    .container-fluid {
        padding: 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const excelFile = document.getElementById('excelFile');
    const previewBtn = document.getElementById('previewBtn');
    const resetBtn = document.getElementById('resetBtn');
    const configSelect = document.getElementById('configSelect');
    const configName = document.getElementById('configName');
    const hasHeader = document.getElementById('hasHeader');
    const headerRow = document.getElementById('headerRow');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'), {
        keyboard: true,
        focus: true,
        backdrop: true
    });

    // Vorschau anzeigen
    previewBtn.addEventListener('click', async function() {
        if (!excelFile.files[0]) {
            alert('Bitte wählen Sie zuerst eine Excel-Datei aus.');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', excelFile.files[0]);
        formData.append('header_row', headerRow.value);

        try {
            const response = await fetch('modules/import/preview_excel.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                updatePreviewTable(data.preview);
                previewModal.show();
            } else {
                alert('Fehler beim Laden der Vorschau: ' + data.message);
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten.');
        }
    });

    // Spalten-Zuordnung aktualisieren
    excelFile.addEventListener('change', async function() {
        if (!this.files[0]) return;

        const formData = new FormData();
        formData.append('excel_file', this.files[0]);
        formData.append('header_row', headerRow.value);

        try {
            const response = await fetch('modules/import/detect_headers.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            console.log('Erkannte Spalten:', data); // Debug-Log

            if (data.success) {
                updateColumnMapping(data.columns, data.suggested_mapping);
            } else {
                alert('Fehler beim Erkennen der Spalten: ' + data.message);
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten.');
        }
    });

    // Formular zurücksetzen
    resetBtn.addEventListener('click', function() {
        importForm.reset();
        document.getElementById('columnMappingContainer').innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-arrow-left-circle display-4 d-block mb-3"></i>
                <p>Bitte wählen Sie eine Excel-Datei aus, um die Spalten zuzuordnen.</p>
            </div>
        `;
    });

    // Konfiguration laden
    configSelect.addEventListener('change', function() {
        if (this.value) {
            configName.value = this.options[this.selectedIndex].text;
            configName.disabled = true;
            loadColumnMapping(this.value);
        } else {
            configName.value = '';
            configName.disabled = false;
        }
    });

    // Hilfsfunktionen
    function updatePreviewTable(preview) {
        const thead = document.querySelector('#previewTable thead');
        const tbody = document.querySelector('#previewTable tbody');
        thead.innerHTML = '';
        tbody.innerHTML = '';

        if (preview.length > 0) {
            // Überschriften
            const headerRow = document.createElement('tr');
            Object.keys(preview[0]).forEach(key => {
                const th = document.createElement('th');
                th.textContent = key;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);

            // Daten
            preview.forEach(row => {
                const tr = document.createElement('tr');
                Object.values(row).forEach(value => {
                    const td = document.createElement('td');
                    td.className = 'preview-cell';
                    td.textContent = value;
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
        }
    }

    function updateColumnMapping(columns, suggestedMapping = {}) {
        console.log('Update Column Mapping:', { columns, suggestedMapping }); // Debug-Log
        
        if (!columns || typeof columns !== 'object') {
            console.error('Keine gültigen Spalten übergeben');
            return;
        }

        const container = document.getElementById('columnMappingContainer');
        container.innerHTML = '';

        // Standard-Spalten
        const standardColumns = [
            { field: 'datum', name: 'Datum', required: true },
            { field: 'beleg_nr', name: 'Beleg-Nr.', required: true },
            { field: 'bemerkung', name: 'Bemerkung', required: true },
            { field: 'einnahme', name: 'Einnahme', required: true },
            { field: 'ausgabe', name: 'Ausgabe', required: true }
        ];

        // Erstelle Mapping-Elemente
        standardColumns.forEach(col => {
            const div = document.createElement('div');
            div.className = 'column-mapping-item';
            
            // Erstelle die Spaltenoptionen
            const columnOptions = Object.entries(columns).map(([key, value]) => {
                const selected = suggestedMapping[col.field] === key ? 'selected' : '';
                return `<option value="${key}" ${selected}>${value}</option>`;
            }).join('');

            div.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label mb-0 fw-bold" for="mapping_${col.field}">
                            ${col.name}
                            ${col.required ? '<span class="text-danger">*</span>' : ''}
                        </label>
                    </div>
                    <div class="col-md-8">
                        <select class="form-select form-select-sm" 
                                id="mapping_${col.field}"
                                name="mapping[${col.field}]" 
                                ${col.required ? 'required' : ''}>
                            <option value="">Bitte wählen...</option>
                            ${columnOptions}
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(div);

            // Debug-Log für jedes erstellte Select-Element
            console.log(`Select für ${col.field} erstellt:`, document.getElementById(`mapping_${col.field}`));
        });

        // Zusätzliche Spalten Button
        const addButton = document.createElement('button');
        addButton.className = 'btn btn-outline-primary btn-sm mt-3';
        addButton.type = 'button';
        addButton.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Zusätzliche Spalte hinzufügen';
        addButton.onclick = function() {
            const div = document.createElement('div');
            div.className = 'column-mapping-item';
            
            const customId = 'custom_' + Date.now();
            div.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" 
                               id="custom_name_${customId}"
                               name="custom_names[]" 
                               placeholder="Spaltenname" required>
                    </div>
                    <div class="col-md-7">
                        <select class="form-select form-select-sm" 
                                id="custom_mapping_${customId}"
                                name="custom_mappings[]" required>
                            <option value="">Bitte wählen...</option>
                            ${Object.entries(columns).map(([key, value]) => 
                                `<option value="${key}">${value}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="this.closest('.column-mapping-item').remove()">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.insertBefore(div, addButton);
        };
        container.appendChild(addButton);
    }

    async function loadColumnMapping(configId) {
        try {
            const response = await fetch(`modules/import/column_mapping.php?config_id=${configId}`);
            const data = await response.json();

            if (data.success) {
                // Setze die gespeicherten Mappings
                Object.entries(data.mapping).forEach(([field, value]) => {
                    const select = document.querySelector(`select[name="mapping[${field}]"]`);
                    if (select) select.value = value;
                });

                // Setze die benutzerdefinierten Spalten
                if (data.custom_mappings) {
                    data.custom_mappings.forEach(mapping => {
                        document.querySelector('#addCustomColumn').click();
                        const items = document.querySelectorAll('.column-mapping-item');
                        const last = items[items.length - 1];
                        last.querySelector('input[name="custom_names[]"]').value = mapping.name;
                        last.querySelector('select[name="custom_mappings[]"]').value = mapping.column;
                    });
                }
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Laden der Konfiguration');
        }
    }

    // Formular-Submit
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Debug-Logging
        console.log('Formular wird gesendet');
        const formData = new FormData(this);
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        // Validierung
        const mappingSelects = document.querySelectorAll('select[name^="mapping["]');
        let hasMapping = false;
        let mappingData = {};
        
        mappingSelects.forEach(select => {
            if (select.value) {
                hasMapping = true;
                // Extrahiere den Feldnamen aus dem name-Attribut
                const fieldName = select.name.match(/\[(.*?)\]/)[1];
                mappingData[fieldName] = select.value;
            }
        });

        console.log('Mapping-Daten:', mappingData); // Debug-Log

        if (!hasMapping) {
            alert('Bitte ordnen Sie mindestens eine Spalte zu.');
            return;
        }

        // Füge die Mapping-Daten zum FormData hinzu
        formData.set('mapping', JSON.stringify(mappingData));

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Server-Antwort:', result); // Debug-Log
            
            if (result.success) {
                showOverlay(result.message, result.type || 'success');
                setTimeout(() => {
                    window.location.href = 'kassenbuch.php';
                }, 2000);
            } else {
                showOverlay(result.message || 'Ein Fehler ist aufgetreten', result.type || 'error');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten.');
        }
    });

    // Modal-Handling
    const previewModalEl = document.getElementById('previewModal');
    previewModalEl.addEventListener('shown.bs.modal', function () {
        // Setze den Fokus auf den ersten interaktiven Element im Modal
        const firstFocusable = this.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    });

    previewModalEl.addEventListener('hidden.bs.modal', function () {
        // Setze den Fokus zurück auf den Preview-Button
        previewBtn.focus();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 