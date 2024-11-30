<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!is_admin()) {
    handle_forbidden();
}

$success_message = '';
$error_message = '';

// Einstellungen aus der Datenbank laden
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Startbetrag-Informationen abrufen
$startbetrag_query = $conn->query("SELECT datum, einnahme as betrag FROM kassenbuch_eintraege WHERE bemerkung = 'Startbetrag' ORDER BY datum DESC LIMIT 1");
$startbetrag_info = $startbetrag_query->fetch_assoc();
$startbetrag = $startbetrag_info['betrag'] ?? 0;
$startbetrag_datum = $startbetrag_info['datum'] ?? date('Y-m-d');

// Am Anfang der Datei nach dem Laden der Settings
$site_name = $settings['site_name'] ?? '';
$logo_light = $settings['logo_light'] ?? 'images/logo_light.png';
$logo_dark = $settings['logo_dark'] ?? 'images/logo_dark.png';

// Setze den Seitentitel - nur wenn ein Seitenname existiert
$page_title = $site_name ? "Einstellungen - " . htmlspecialchars($site_name) : "Einstellungen";

// Am Anfang der Datei nach dem Laden der Settings
$custom_columns = [];

// Hole alle Spalten aus der kassenbuch_eintraege Tabelle
$columns_query = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege");
$default_columns = ['id', 'datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe', 'saldo', 'kassenstand', 'user_id', 'created_at'];

while ($column = $columns_query->fetch_assoc()) {
    // Wenn es keine Standard-Spalte ist, füge sie zu custom_columns hinzu
    if (!in_array($column['Field'], $default_columns)) {
        $type = match($column['Type']) {
            'varchar(255)' => 'text',
            'date' => 'date',
            'decimal(10,2)' => 'decimal',
            'int' => 'integer',
            default => 'text'
        };
        
        $custom_columns[] = [
            'name' => $column['Field'],
            'type' => $type,
            'excel_column' => '' // Standardmäßig leer, muss manuell gesetzt werden
        ];
    }
}

// Speichere die custom_columns in den Settings
if (!empty($custom_columns)) {
    $custom_columns_json = json_encode($custom_columns);
    updateSetting($conn, 'custom_columns', $custom_columns_json);
    error_log("Custom columns aktualisiert: " . $custom_columns_json);
}

// Am Anfang der Datei nach dem Laden der Settings
$default_columns = [
    'datum' => ['name' => 'Datum', 'type' => 'date', 'required' => true],
    'beleg_nr' => ['name' => 'Beleg-Nr.', 'type' => 'text', 'required' => true],
    'bemerkung' => ['name' => 'Bemerkung', 'type' => 'text', 'required' => true],
    'einnahme' => ['name' => 'Einnahme', 'type' => 'decimal', 'required' => true],
    'ausgabe' => ['name' => 'Ausgabe', 'type' => 'decimal', 'required' => true]
];

// Lade gespeicherte Spaltenkonfiguration oder verwende Standardwerte
$columns_config = json_decode($settings['columns_config'] ?? '[]', true) ?: [];

// Hole alle Spalten aus der kassenbuch_eintraege Tabelle
$columns_query = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege");
$system_columns = ['id', 'saldo', 'kassenstand', 'user_id', 'created_at'];
$existing_custom_columns = [];

while ($column = $columns_query->fetch_assoc()) {
    // Wenn es keine Standard- oder System-Spalte ist
    if (!isset($default_columns[$column['Field']]) && !in_array($column['Field'], $system_columns)) {
        $type = match($column['Type']) {
            'varchar(255)' => 'text',
            'date' => 'date',
            'decimal(10,2)' => 'decimal',
            'int' => 'integer',
            default => 'text'
        };
        
        // Füge zur columns_config hinzu, wenn noch nicht vorhanden
        if (!isset($columns_config[$column['Field']])) {
            $columns_config[$column['Field']] = [
                'display_name' => ucfirst(str_replace('_', ' ', $column['Field'])),
                'type' => $type,
                'required' => false,
                'visible' => true,
                'excel_column' => ''
            ];
        }
        
        $existing_custom_columns[] = $column['Field'];
    }
}

// Initialisiere Standard-Spalten wenn noch nicht vorhanden
foreach ($default_columns as $key => $config) {
    if (!isset($columns_config[$key])) {
        $columns_config[$key] = [
            'display_name' => $config['name'],
            'type' => $config['type'],
            'required' => $config['required'],
            'visible' => true,
            'excel_column' => ''
        ];
    }
}

// Speichere aktualisierte Konfiguration
updateSetting($conn, 'columns_config', json_encode($columns_config));

// Verarbeite Formular-Submission für Spalten-Konfiguration
if (isset($_POST['save_columns_config'])) {
    try {
        $conn->begin_transaction();
        
        $new_columns_config = [];
        
        // Verarbeite Standard-Spalten
        if (isset($_POST['columns']['default']) && is_array($_POST['columns']['default'])) {
            foreach ($_POST['columns']['default'] as $key => $column) {
                if (isset($default_columns[$key])) { // Nur erlaubte Standard-Spalten
                    $new_columns_config[$key] = [
                        'display_name' => $column['display_name'],
                        'type' => $default_columns[$key]['type'], // Typ kann nicht geändert werden
                        'required' => $default_columns[$key]['required'], // Required-Status bleibt fix
                        'visible' => isset($column['visible']) && $column['visible'] === 'true',
                        'excel_column' => $column['excel_column'] ?? ''
                    ];
                }
            }
        }
        
        // Verarbeite benutzerdefinierte Spalten
        if (isset($_POST['columns']['custom']) && is_array($_POST['columns']['custom'])) {
            foreach ($_POST['columns']['custom'] as $column) {
                if (isset($column['name'], $column['type'], $column['excel_column']) &&
                    !empty($column['name'])) {
                    
                    $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
                    
                    // SQL-Typ bestimmen
                    $sql_type = match($column['type']) {
                        'text' => 'VARCHAR(255)',
                        'date' => 'DATE',
                        'decimal' => 'DECIMAL(10,2)',
                        'integer' => 'INT',
                        default => 'VARCHAR(255)'
                    };
                    
                    // Spalte zur DB hinzufügen/aktualisieren wenn nötig
                    $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
                    if ($result->num_rows === 0) {
                        $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN $column_name $sql_type";
                        $conn->query($sql);
                    }
                    
                    $new_columns_config[$column_name] = [
                        'display_name' => $column['display_name'] ?? $column['name'],
                        'type' => $column['type'],
                        'required' => false,
                        'visible' => isset($column['visible']) && $column['visible'] === 'true',
                        'excel_column' => $column['excel_column']
                    ];
                }
            }
        }
        
        // Speichere aktualisierte Konfiguration
        updateSetting($conn, 'columns_config', json_encode($new_columns_config));
        
        $conn->commit();
        $success_message = 'Spaltenkonfiguration wurde erfolgreich gespeichert.';
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = 'Fehler beim Speichern der Konfiguration: ' . $e->getMessage();
    }
}

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verarbeite Kassenstart-Formular
        if (isset($_POST['startbetrag']) && isset($_POST['startdatum'])) {
            try {
                $startbetrag = str_replace(',', '.', $_POST['startbetrag']);
                $startdatum = $_POST['startdatum'];
                
                if (!is_numeric($startbetrag)) {
                    throw new Exception('Kassenstart muss eine Zahl sein.');
                }

                // Beginne Transaktion
                $conn->begin_transaction();

                // 1. Speichere in settings Tabelle
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                       VALUES ('cash_start', ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $startbetrag, $startbetrag);
                
                if (!$stmt->execute()) {
                    throw new Exception("Fehler beim Speichern des Kassenstarts in Settings: " . $stmt->error);
                }

                // 2. Prüfe ob bereits ein Kassenstart existiert
                $check_stmt = $conn->prepare("SELECT id FROM kassenbuch_eintraege WHERE bemerkung = 'Kassenstart' LIMIT 1");
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existierenden Kassenstart
                    $row = $result->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE kassenbuch_eintraege 
                        SET datum = ?, einnahme = ?, saldo = ?, kassenstand = ?
                        WHERE id = ?");
                    
                    $saldo = floatval($startbetrag);
                    $stmt->bind_param("sdddi", 
                        $startdatum,
                        $startbetrag,
                        $saldo,
                        $saldo,
                        $row['id']
                    );
                } else {
                    // Erstelle neuen Kassenstart-Eintrag
                    $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege 
                        (datum, bemerkung, einnahme, ausgabe, saldo, kassenstand, user_id) 
                        VALUES (?, 'Kassenstart', ?, 0, ?, ?, ?)");
                    
                    $saldo = floatval($startbetrag);
                    $stmt->bind_param("sdddi", 
                        $startdatum,
                        $startbetrag,
                        $saldo,
                        $saldo,
                        $_SESSION['user_id']
                    );
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Fehler beim Speichern des Kassenstarts im Kassenbuch: " . $stmt->error);
                }

                // Commit Transaktion
                $conn->commit();
                $success_message = 'Kassenstart wurde erfolgreich gespeichert.';
                
            } catch (Exception $e) {
                // Rollback bei Fehler
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
        
        // Logo-Upload für hellen Modus
        if (isset($_FILES['logo_light']) && $_FILES['logo_light']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = handleLogoUpload($_FILES['logo_light'], 'logo_light');
            updateSetting($conn, 'logo_light', $uploadPath);
        }
        
        // Logo-Upload für dunklen Modus
        if (isset($_FILES['logo_dark']) && $_FILES['logo_dark']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = handleLogoUpload($_FILES['logo_dark'], 'logo_dark');
            updateSetting($conn, 'logo_dark', $uploadPath);
        }
        
        // Seitenname aktualisieren
        if (isset($_POST['site_name'])) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES ('site_name', ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $_POST['site_name'], $_POST['site_name']);
            
            if (!$stmt->execute()) {
                throw new Exception("Fehler beim Speichern des Seitennamens: " . $stmt->error);
            }
            
            // Aktualisiere die lokale Variable
            $site_name = $_POST['site_name'];
            $success_message = 'Einstellungen wurden erfolgreich gespeichert.';
            
            // Lade die Settings neu
            $result = $conn->query("SELECT setting_key, setting_value FROM settings");
            if ($result) {
                $settings = [];
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        
        // Kassenstart aktualisieren
        if (isset($_POST['cash_start'])) {
            $cashStart = str_replace(',', '.', $_POST['cash_start']);
            if (!is_numeric($cashStart)) {
                throw new Exception('Kassenstart muss eine Zahl sein.');
            }
            updateSetting($conn, 'cash_start', $cashStart);
        }
        
        $success_message = 'Einstellungen wurden erfolgreich gespeichert.';
        
        // Einstellungen neu laden
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        
        // Am Anfang der Datei, wo die POST-Verarbeitung stattfindet
        if (isset($_POST['save_excel_config'])) {
            try {
                // Speichere Excel-Mapping
                foreach ($_POST['excel_mapping'] as $field => $column) {
                    $key = 'excel_mapping_' . $field;
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                           VALUES (?, ?) 
                                           ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("sss", $key, $column, $column);
                    $stmt->execute();
                }

                // Speichere Excel-Format-Einstellungen
                foreach ($_POST['excel_format'] as $key => $value) {
                    $setting_key = 'excel_' . $key;
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                           VALUES (?, ?) 
                                           ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->bind_param("sss", $setting_key, $value, $value);
                    $stmt->execute();
                }

                $success_message = 'Excel-Import-Konfiguration wurde gespeichert.';
            } catch (Exception $e) {
                $error_message = 'Fehler beim Speichern der Konfiguration: ' . $e->getMessage();
            }
        }
        
        // Verarbeite die benutzerdefinierten Spalten
        if (isset($_POST['save_column_config'])) {
            try {
                $conn->begin_transaction();
                
                $custom_columns = [];
                
                if (isset($_POST['columns']['custom']) && is_array($_POST['columns']['custom'])) {
                    foreach ($_POST['columns']['custom'] as $column) {
                        if (isset($column['name'], $column['type'], $column['excel_column']) &&
                            !empty($column['name']) && !empty($column['type']) && !empty($column['excel_column'])) {
                            
                            // Spaltenname für DB normalisieren
                            $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
                            
                            // SQL-Typ bestimmen
                            $sql_type = match($column['type']) {
                                'text' => 'VARCHAR(255)',
                                'date' => 'DATE',
                                'decimal' => 'DECIMAL(10,2)',
                                'integer' => 'INT',
                                default => 'VARCHAR(255)'
                            };
                            
                            // Spalte zur DB hinzufügen wenn sie nicht existiert
                            $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
                            if ($result->num_rows === 0) {
                                $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN $column_name $sql_type";
                                $conn->query($sql);
                            }
                            
                            // Zum custom_columns Array hinzufügen
                            $custom_columns[] = [
                                'name' => $column_name,
                                'type' => $column['type'],
                                'excel_column' => $column['excel_column']
                            ];
                        }
                    }
                    
                    // Speichere aktualisierte custom_columns
                    updateSetting($conn, 'custom_columns', json_encode($custom_columns));
                }
                
                $conn->commit();
                $success_message = 'Spaltenkonfiguration wurde erfolgreich gespeichert.';
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Fehler beim Speichern der Konfiguration: ' . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Hilfsfunktionen
function handleLogoUpload($file, $prefix) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Nur JPEG, PNG und GIF Dateien sind erlaubt.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Die Datei ist zu groß. Maximale Größe ist 5MB.');
    }
    
    $uploadDir = __DIR__ . '/images/';
    
    // Erstelle das Verzeichnis, falls es nicht existiert
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Konnte Upload-Verzeichnis nicht erstellen.');
        }
    }
    
    // Prüfe Schreibrechte
    if (!is_writable($uploadDir)) {
        throw new Exception('Keine Schreibrechte im Upload-Verzeichnis.');
    }
    
    // Hier wird der Dateiname standardisiert
    $fileName = $prefix . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $uploadPath = $uploadDir . $fileName;
    
    // Entferne alte Datei falls vorhanden
    $existingFiles = glob($uploadDir . $prefix . '.*');
    foreach ($existingFiles as $existingFile) {
        if (file_exists($existingFile)) {
            unlink($existingFile);
        }
    }
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Fehler beim Hochladen der Datei.');
    }
    
    // Gebe relativen Pfad zurück
    return 'images/' . $fileName;
}

function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                           VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
}

require_once 'includes/header.php';
?>

<div class="settings-page">
    <div class="container">
        <div class="settings-header d-flex justify-content-between align-items-center">
            <h1>Einstellungen</h1>
            <div class="btn-group">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear"></i> Administration
                </button>
                <ul class="dropdown-menu">
                    <!-- Dropdown-Menü-Einträge -->
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#basis" role="tab">
                            <i class="bi bi-gear"></i> Basis
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Basis Tab -->
                    <div class="tab-pane fade show active" id="basis" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kasseneinstellungen -->
                            <div class="col-12">
                                <div class="settings-section">
                                    <h5 class="mb-4"><i class="bi bi-cash"></i> Kasseneinstellungen</h5>
                                    <div class="row g-4">
                                        <!-- Kassenstart -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3">Kassenstart</h6>
                                                    <?php if ($startbetrag_info): ?>
                                                    <div class="alert alert-info">
                                                        <strong>Aktueller Startbetrag:</strong> 
                                                        <?= number_format($startbetrag_info['betrag'], 2, ',', '.') ?> € 
                                                        (<?= date('d.m.Y', strtotime($startbetrag_info['datum'])) ?>)
                                                    </div>
                                                    <?php endif; ?>
                                                    <form id="kassenstartForm">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Datum</label>
                                                                <input type="date" class="form-control" id="startdatum" name="startdatum" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Betrag (€)</label>
                                                                <input type="number" step="0.01" class="form-control" id="startbetrag" name="startbetrag" required>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary mt-3">
                                                            <i class="bi bi-save"></i> Speichern
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kassensturz -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3">Kassensturz</h6>
                                                    <form id="kassensturzForm">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Datum</label>
                                                                <input type="date" class="form-control" id="kassensturz_datum" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Ist-Betrag (€)</label>
                                                                <input type="number" step="0.01" class="form-control" id="kassensturz_betrag" required>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary mt-3">
                                                            <i class="bi bi-calculator"></i> Durchführen
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Logo Einstellungen -->
                            <div class="col-12">
                                <div class="settings-section">
                                    <h5 class="mb-4"><i class="bi bi-image"></i> Logo Einstellungen</h5>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3">Logo (Hell)</h6>
                                                    <div class="logo-preview mb-3">
                                                        <?php if (!empty($settings['logo_light'])): ?>
                                                            <img src="<?= htmlspecialchars($settings['logo_light']) ?>" alt="Helles Logo">
                                                        <?php else: ?>
                                                            <div class="upload-placeholder">
                                                                <i class="bi bi-cloud-upload"></i>
                                                                <span>Logo hochladen</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" class="form-control" name="logo_light" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3">Logo (Dunkel)</h6>
                                                    <div class="logo-preview dark mb-3">
                                                        <?php if (!empty($settings['logo_dark'])): ?>
                                                            <img src="<?= htmlspecialchars($settings['logo_dark']) ?>" alt="Dunkles Logo">
                                                        <?php else: ?>
                                                            <div class="upload-placeholder">
                                                                <i class="bi bi-cloud-upload"></i>
                                                                <span>Logo hochladen</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" class="form-control" name="logo_dark" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Weitere Basis-Einstellungen -->
                            <div class="col-12">
                                <div class="settings-section">
                                    <h5 class="mb-4"><i class="bi bi-gear"></i> Weitere Einstellungen</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Seitenname</label>
                                                    <input type="text" class="form-control" name="site_name" 
                                                           value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                                                </div>
                                                <!-- Hier können weitere Einstellungen hinzugefügt werden -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5"></div>

<?php require_once 'includes/footer.php'; ?> 

<script src="js/settings.js" defer></script>

<script>
document.getElementById('addColumnBtn').addEventListener('click', function() {
    const container = document.getElementById('customColumnsContainer');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="text" class="form-control" name="columns[custom][][name]" required>
        </td>
        <td>
            <input type="text" class="form-control" name="columns[custom][][display_name]" required>
        </td>
        <td>
            <select class="form-select" name="columns[custom][][type]">
                <option value="text">Text</option>
                <option value="date">Datum</option>
                <option value="decimal">Dezimal</option>
                <option value="integer">Ganzzahl</option>
            </select>
        </td>
        <td>
            <select class="form-select" name="columns[custom][][excel_column]">
                <option value="">-</option>
                ${Array.from(Array(26)).map((_, i) => 
                    `<option value="${String.fromCharCode(65 + i)}">${String.fromCharCode(65 + i)}</option>`
                ).join('')}
            </select>
        </td>
        <td>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="columns[custom][][visible]" checked>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-outline-danger btn-sm delete-column">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(newRow);
});

// Event-Delegation für Delete-Buttons
document.getElementById('customColumnsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.delete-column')) {
        if (confirm('Möchten Sie diese Spalte wirklich löschen?')) {
            e.target.closest('tr').remove();
        }
    }
});
</script>

</body>
</html> 