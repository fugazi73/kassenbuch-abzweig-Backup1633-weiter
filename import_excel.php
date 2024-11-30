<?php
require_once 'config.php';
require_once 'includes/init.php';
check_login();
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

// Lade gespeicherte Excel-Spalten-Zuordnungen für Standard-Spalten
$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'excel_mapping_%'");
$saved_mappings = [];
while ($row = $result->fetch_assoc()) {
    $column_name = str_replace('excel_mapping_', '', $row['setting_key']);
    $saved_mappings[$column_name] = $row['setting_value'];
}

// Lade benutzerdefinierte Spalten
$custom_columns = [];
$result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'custom_columns'");
if ($row = $result->fetch_assoc()) {
    $custom_columns = json_decode($row['setting_value'], true) ?: [];
}
?>

<div class="container mt-4">
    <!-- Header-Bereich -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-light">Excel-Import</h4>
            <p class="text-muted mb-0">Konfigurieren Sie den Import Ihrer Kassenbuch-Daten</p>
        </div>
        <div>
            <a href="download_template.php" class="btn btn-outline-light">
                <i class="bi bi-download me-2"></i>Excel-Vorlage
            </a>
        </div>
    </div>

    <form method="POST" action="process_columns.php" id="columnsForm">
        <!-- Standard-Spalten -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5>Standard-Spalten</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Excel-Spalte</th>
                                <th>Typ</th>
                                <th>Pflichtfeld</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standard_columns as $key => $config): ?>
                            <tr>
                                <td>
                                    <span class="text-muted">
                                        <i class="bi bi-lock-fill me-1"></i>
                                        <?= htmlspecialchars($config['display_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" 
                                            name="columns[standard][<?= $key ?>][excel_column]" required>
                                        <option value="">-</option>
                                        <?php for ($i = 0; $i < 26; $i++): ?>
                                            <?php $letter = chr(65 + $i); ?>
                                            <option value="<?= $letter ?>" 
                                                <?= ($saved_mappings[$key] ?? '') === $letter ? 'selected' : '' ?>>
                                                <?= $letter ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $config['type'] ?></span>
                                </td>
                                <td>
                                    <?php if ($config['required']): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Benutzerdefinierte Spalten -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5>Benutzerdefinierte Spalten</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-success btn-sm" id="addCustomColumn">
                        <i class="bi bi-plus-lg"></i> Neue Spalte
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="customColumnsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Excel-Spalte</th>
                                <th>Typ</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($custom_columns as $key => $column): ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="columns[custom][<?= $key ?>][name]" 
                                           value="<?= htmlspecialchars($column['name']) ?>" required>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" 
                                            name="columns[custom][<?= $key ?>][excel_column]" required>
                                        <option value="">-</option>
                                        <?php for ($i = 0; $i < 26; $i++): ?>
                                            <?php $letter = chr(65 + $i); ?>
                                            <option value="<?= $letter ?>" 
                                                <?= ($column['excel_column'] ?? '') === $letter ? 'selected' : '' ?>>
                                                <?= $letter ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" 
                                            name="columns[custom][<?= $key ?>][type]" required>
                                        <option value="text" <?= ($column['type'] ?? '') === 'text' ? 'selected' : '' ?>>Text</option>
                                        <option value="date" <?= ($column['type'] ?? '') === 'date' ? 'selected' : '' ?>>Datum</option>
                                        <option value="decimal" <?= ($column['type'] ?? '') === 'decimal' ? 'selected' : '' ?>>Dezimal</option>
                                        <option value="integer" <?= ($column['type'] ?? '') === 'integer' ? 'selected' : '' ?>>Ganzzahl</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm delete-column">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" id="saveColumns">
                    <i class="bi bi-save"></i> Spaltenkonfiguration speichern
                </button>
            </div>
        </div>
    </form>

    <!-- Upload-Bereich im Dark Mode -->
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-header bg-dark border-secondary">
            <h5 class="text-light mb-0">Excel-Datei importieren</h5>
        </div>
        <div class="card-body">
            <form action="process_excel_upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-container mb-4">
                    <div class="upload-area p-5 text-center position-relative">
                        <div class="upload-content">
                            <i class="bi bi-file-earmark-excel display-4 text-success mb-3"></i>
                            <h5 class="mb-3 text-light">Excel-Datei auswählen oder hierher ziehen</h5>
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-light btn-lg px-4" id="selectFileBtn">
                                    <i class="bi bi-folder2-open me-2"></i>Datei auswählen
                                </button>
                                <input type="file" class="d-none" id="excelFile" name="excelFile" 
                                       accept=".xlsx,.xls" required>
                            </div>
                            <div class="selected-file-name text-light-50"></div>
                            <small class="d-block mt-2 text-secondary">
                                Unterstützte Formate: .xlsx, .xls
                            </small>
                        </div>
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="skipFirstRow" name="skipFirstRow" checked>
                            <label class="form-check-label text-light" for="skipFirstRow">
                                Erste Zeile überspringen (Überschriften)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button type="submit" class="btn btn-primary" id="uploadButton">
                            <i class="bi bi-upload me-2"></i>Datei importieren
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast für Benachrichtigungen -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<style>
/* Dark Mode Styles */
.upload-area {
    transition: all 0.3s ease;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.upload-area:hover, .upload-area.dragover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #0d6efd;
}

.upload-content {
    width: 100%;
}

.selected-file-name {
    margin-top: 1rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
}

/* Toast im Dark Mode */
.toast {
    background: rgba(25, 135, 84, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.toast.error {
    background: rgba(220, 53, 69, 0.9);
}

/* Anpassungen für Formularelemente im Dark Mode */
.form-check-input {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Hover-Effekte */
.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Animation für Drag & Drop */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.upload-area.dragover {
    animation: pulse 0.5s ease;
    box-shadow: 0 0 15px rgba(13, 110, 253, 0.3);
}
</style>

<script>
// Toast-Funktion für Benachrichtigungen
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toast.classList.remove('bg-success', 'bg-danger');
    toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    toast.style.backgroundColor = type === 'success' ? 'rgba(25, 135, 84, 0.9)' : 'rgba(220, 53, 69, 0.9)';
    toastMessage.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

// Dateiauswahl-Button
document.getElementById('selectFileBtn').addEventListener('click', () => {
    document.getElementById('excelFile').click();
});

// Dateiauswahl-Anzeige
document.getElementById('excelFile').addEventListener('change', function() {
    const fileName = this.files[0]?.name;
    if (fileName) {
        document.querySelector('.selected-file-name').textContent = `Ausgewählte Datei: ${fileName}`;
        showToast('Datei ausgewählt: ' + fileName);
    }
});

// Drag & Drop Funktionalität
const uploadArea = document.querySelector('.upload-area');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.add('dragover');
    });
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => {
        uploadArea.classList.remove('dragover');
    });
});

uploadArea.addEventListener('drop', handleDrop);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    document.getElementById('excelFile').files = files;
    
    if (files[0]) {
        document.querySelector('.selected-file-name').textContent = 
            `Ausgewählte Datei: ${files[0].name}`;
        showToast('Datei ausgewählt: ' + files[0].name);
    }
}

// Nach erfolgreichem Speichern der Spaltenkonfiguration
document.getElementById('columnsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const response = await fetch('process_columns.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Spaltenkonfiguration wurde erfolgreich gespeichert');
        } else {
            showToast(result.message || 'Fehler beim Speichern', 'error');
        }
    } catch (error) {
        showToast('Ein Fehler ist aufgetreten', 'error');
        console.error('Fehler:', error);
    }
});

document.getElementById('addCustomColumn').addEventListener('click', function() {
    const tbody = document.querySelector('#customColumnsTable tbody');
    const rowCount = tbody.children.length;
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" 
                   name="columns[custom][new_${rowCount}][name]" required>
        </td>
        <td>
            <select class="form-select form-select-sm" 
                    name="columns[custom][new_${rowCount}][excel_column]" required>
                <option value="">-</option>
                ${Array.from(Array(26)).map((_, i) => 
                    `<option value="${String.fromCharCode(65 + i)}">${String.fromCharCode(65 + i)}</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" 
                    name="columns[custom][new_${rowCount}][type]" required>
                <option value="text">Text</option>
                <option value="date">Datum</option>
                <option value="decimal">Dezimal</option>
                <option value="integer">Ganzzahl</option>
            </select>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm delete-column">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
});

document.addEventListener('DOMContentLoaded', function() {
    // Lösch-Buttons Event Handler
    document.querySelectorAll('.bi-trash').forEach(button => {
        button.closest('button').addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Bestätigungsdialog
            if (!confirm('Möchten Sie diese Spalte wirklich löschen?')) {
                return;
            }

            const row = this.closest('tr');
            const nameInput = row.querySelector('input');
            const columnName = nameInput.value;

            try {
                const response = await fetch('delete_column.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ columnName: columnName })
                });

                const data = await response.json();

                if (data.success) {
                    // Zeile entfernen
                    row.remove();
                    // Optional: Erfolgsmeldung anzeigen
                    alert('Spalte wurde erfolgreich gelöscht');
                } else {
                    throw new Error(data.error || 'Fehler beim Löschen');
                }
            } catch (error) {
                alert('Fehler: ' + error.message);
                console.error('Fehler:', error);
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
