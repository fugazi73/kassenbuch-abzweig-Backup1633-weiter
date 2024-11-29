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
$default_columns = ['id', 'datum', 'beleg_nr', 'beleg', 'bemerkung', 'einnahme', 'ausgabe', 'saldo', 'kassenstand', 'user_id', 'created_at'];

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
    'beleg' => ['name' => 'Beleg', 'type' => 'text', 'required' => false],
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

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Einstellungen</h1>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear"></i> Administration
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="admin.php"><i class="bi bi-people"></i> Benutzerverwaltung</a></li>
                        <li><a class="dropdown-item" href="backup.php"><i class="bi bi-download"></i> Backup & Restore</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Einstellungen</a></li>
                    </ul>
                </div>
            </div>

            <!-- Tabs für verschiedene Einstellungsbereiche -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#basics" role="tab">
                                <i class="bi bi-gear-fill"></i> Basis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#columns" role="tab">
                                <i class="bi bi-table"></i> Spalten
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#cash" role="tab">
                                <i class="bi bi-cash"></i> Kasse
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content">
                        <!-- Basis-Einstellungen Tab -->
                        <div class="tab-pane fade show active p-3" id="basics" role="tabpanel">
                            <div class="row g-3">
                                <!-- Logo-Upload Bereich -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-image"></i> Logos</h6>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="d-block mb-2">Hell</label>
                                                    <div class="logo-preview mb-2">
                                                        <?php if (!empty($settings['logo_light'])): ?>
                                                            <img src="<?= htmlspecialchars($logo_light) ?>" class="img-fluid">
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" class="form-control form-control-sm" name="logo_light">
                                                </div>
                                                <div class="col-6">
                                                    <label class="d-block mb-2">Dunkel</label>
                                                    <div class="logo-preview mb-2 bg-dark">
                                                        <?php if (!empty($settings['logo_dark'])): ?>
                                                            <img src="<?= htmlspecialchars($logo_dark) ?>" class="img-fluid">
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" class="form-control form-control-sm" name="logo_dark">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Seitenname & Weitere Basis-Einstellungen -->
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-pencil"></i> Allgemein</h6>
                                            <div class="mb-3">
                                                <label class="form-label">Seitenname</label>
                                                <input type="text" class="form-control" name="site_name" 
                                                       value="<?= htmlspecialchars($site_name) ?>">
                                            </div>
                                            <!-- Weitere allgemeine Einstellungen hier -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spalten-Konfiguration Tab -->
                        <div class="tab-pane fade p-3" id="columns" role="tabpanel">
                            <div class="accordion" id="columnsAccordion">
                                <!-- Standard-Spalten Accordion -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#standardColumns">
                                            Standard-Spalten
                                        </button>
                                    </h2>
                                    <div id="standardColumns" class="accordion-collapse collapse show">
                                        <div class="accordion-body p-2">
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Spalte</th>
                                                            <th>Anzeigename</th>
                                                            <th>Excel</th>
                                                            <th>Sichtbar</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($columns_config as $key => $config): 
                                                            if (isset($default_columns[$key])): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($key) ?></td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm"
                                                                       name="columns[default][<?= $key ?>][display_name]"
                                                                       value="<?= htmlspecialchars($config['display_name']) ?>">
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm" 
                                                                        name="columns[default][<?= $key ?>][excel_column]">
                                                                    <option value="">-</option>
                                                                    <?php foreach (range('A', 'Z') as $col): ?>
                                                                        <option value="<?= $col ?>" 
                                                                            <?= ($config['excel_column'] === $col) ? 'selected' : '' ?>>
                                                                            <?= $col ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input"
                                                                           name="columns[default][<?= $key ?>][visible]"
                                                                           <?= $config['visible'] ? 'checked' : '' ?>
                                                                           <?= $config['required'] ? 'disabled' : '' ?>>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endif; endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Benutzerdefinierte Spalten Accordion -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customColumns">
                                            Benutzerdefinierte Spalten
                                        </button>
                                    </h2>
                                    <div id="customColumns" class="accordion-collapse collapse">
                                        <div class="accordion-body p-2">
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle" id="customColumnsTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Anzeigename</th>
                                                            <th>Typ</th>
                                                            <th>Excel</th>
                                                            <th>Sichtbar</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($columns_config as $key => $config): 
                                                            if (!isset($default_columns[$key]) && !in_array($key, $system_columns)): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm"
                                                                       name="columns[custom][][name]"
                                                                       value="<?= htmlspecialchars($key) ?>">
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm"
                                                                       name="columns[custom][][display_name]"
                                                                       value="<?= htmlspecialchars($config['display_name']) ?>">
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm" name="columns[custom][][type]">
                                                                    <option value="text" <?= $config['type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                                                    <option value="date" <?= $config['type'] === 'date' ? 'selected' : '' ?>>Datum</option>
                                                                    <option value="decimal" <?= $config['type'] === 'decimal' ? 'selected' : '' ?>>Dezimal</option>
                                                                    <option value="integer" <?= $config['type'] === 'integer' ? 'selected' : '' ?>>Ganzzahl</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm" name="columns[custom][][excel_column]">
                                                                    <option value="">-</option>
                                                                    <?php foreach (range('A', 'Z') as $col): ?>
                                                                        <option value="<?= $col ?>" 
                                                                            <?= ($config['excel_column'] === $col) ? 'selected' : '' ?>>
                                                                            <?= $col ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input type="checkbox" class="form-check-input"
                                                                           name="columns[custom][][visible]"
                                                                           <?= $config['visible'] ? 'checked' : '' ?>>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-column">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php endif; endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="addColumnBtn">
                                                <i class="bi bi-plus-circle"></i> Neue Spalte
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kassen-Einstellungen Tab -->
                        <div class="tab-pane fade p-3" id="cash" role="tabpanel">
                            <div class="row g-3">
                                <!-- Kassenstart -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-cash-stack"></i> Kassenstart</h6>
                                            <?php if ($startbetrag_info): ?>
                                                <div class="alert alert-info py-2">
                                                    Aktuell: <?= number_format($startbetrag_info['betrag'], 2, ',', '.') ?> € 
                                                    (<?= date('d.m.Y', strtotime($startbetrag_info['datum'])) ?>)
                                                </div>
                                            <?php endif; ?>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">Datum</label>
                                                    <input type="date" class="form-control form-control-sm" id="startdatum" name="startdatum">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Betrag (€)</label>
                                                    <input type="number" step="0.01" class="form-control form-control-sm" id="startbetrag" name="startbetrag">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm mt-2">Speichern</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kassensturz -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title"><i class="bi bi-calculator"></i> Kassensturz</h6>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">Datum</label>
                                                    <input type="date" class="form-control form-control-sm" id="kassensturz_datum">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ist-Betrag (€)</label>
                                                    <input type="number" step="0.01" class="form-control form-control-sm" id="kassensturz_betrag">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2">Durchführen</button>
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

</body>
</html> 