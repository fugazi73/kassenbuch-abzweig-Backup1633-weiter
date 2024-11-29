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
        
        // Nach der Validierung der POST-Daten für save_column_config
        if (isset($_POST['save_column_config'])) {
            try {
                $conn->begin_transaction();

                // 1. Speichere die Basis-Spalten-Konfiguration
                foreach ($_POST['columns']['required'] as $key => $config) {
                    $mapping_key = 'excel_mapping_' . $key;
                    updateSetting($conn, $mapping_key, $config['excel_column']);
                }

                // 2. Verarbeite die benutzerdefinierten Spalten
                if (isset($_POST['columns']['custom'])) {
                    $custom_columns = $_POST['columns']['custom'];
                    
                    // Speichere die Spaltenkonfiguration in den Settings
                    updateSetting($conn, 'custom_columns', json_encode($custom_columns));

                    // Hole existierende Spalten aus der Datenbank
                    $existing_columns = [];
                    $columns_result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege");
                    while ($column = $columns_result->fetch_assoc()) {
                        $existing_columns[] = $column['Field'];
                    }

                    // Füge neue Spalten hinzu oder aktualisiere bestehende
                    foreach ($custom_columns as $column) {
                        $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
                        
                        // Bestimme den SQL-Datentyp basierend auf dem gewählten Typ
                        $sql_type = match($column['type']) {
                            'text' => 'VARCHAR(255)',
                            'date' => 'DATE',
                            'decimal' => 'DECIMAL(10,2)',
                            'integer' => 'INT',
                            default => 'VARCHAR(255)'
                        };

                        // Prüfe ob die Spalte bereits existiert
                        if (!in_array($column_name, $existing_columns)) {
                            // Neue Spalte hinzufügen
                            $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN $column_name $sql_type";
                            if (!$conn->query($sql)) {
                                throw new Exception("Fehler beim Hinzufügen der Spalte $column_name: " . $conn->error);
                            }
                        } else {
                            // Existierende Spalte aktualisieren
                            $sql = "ALTER TABLE kassenbuch_eintraege MODIFY COLUMN $column_name $sql_type";
                            if (!$conn->query($sql)) {
                                throw new Exception("Fehler beim Aktualisieren der Spalte $column_name: " . $conn->error);
                            }
                        }
                    }

                    // Entferne nicht mehr benötigte Spalten
                    $custom_column_names = array_map(function($col) {
                        return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $col['name']));
                    }, $custom_columns);

                    // Finde und entferne verwaiste benutzerdefinierte Spalten
                    $preserved_columns = ['id', 'datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe', 'saldo', 'kassenstand', 'user_id'];
                    foreach ($existing_columns as $existing_column) {
                        if (!in_array($existing_column, $preserved_columns) && 
                            !in_array($existing_column, $custom_column_names)) {
                            $sql = "ALTER TABLE kassenbuch_eintraege DROP COLUMN $existing_column";
                            if (!$conn->query($sql)) {
                                throw new Exception("Fehler beim Entfernen der Spalte $existing_column: " . $conn->error);
                            }
                        }
                    }
                }

                $conn->commit();
                $success_message = 'Excel-Import-Konfiguration und Datenbankstruktur wurden erfolgreich aktualisiert.';
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

<div class="container mt-4 mb-5">
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
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <!-- Kassenstart-Formular -->
                    <form method="POST" class="mb-4">
                        <h5>Kassenstart</h5>
                        <?php
                        // Aktuellen Startbetrag anzeigen
                        $startbetrag_query = $conn->query("
                            SELECT datum, einnahme as betrag 
                            FROM kassenbuch_eintraege 
                            WHERE bemerkung = 'Kassenstart' 
                            ORDER BY datum DESC, id DESC 
                            LIMIT 1
                        ");
                        $startbetrag_info = $startbetrag_query->fetch_assoc();
                        if ($startbetrag_info): ?>
                            <div class="alert alert-info">
                                Aktueller Startbetrag: <?= number_format($startbetrag_info['betrag'], 2, ',', '.') ?> € 
                                (Datum: <?= date('d.m.Y', strtotime($startbetrag_info['datum'])) ?>)
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label for="startdatum" class="form-label">Datum</label>
                                <input type="date" class="form-control" id="startdatum" name="startdatum" required>
                            </div>
                            <div class="col-auto">
                                <label for="startbetrag" class="form-label">Betrag (€)</label>
                                <input type="number" step="0.01" class="form-control" id="startbetrag" name="startbetrag" required>
                            </div>
                            <div class="col-auto">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Kassenstart speichern</button>
                            </div>
                        </div>
                    </form>

                    <!-- Allgemeine Einstellungen Formular -->
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Logo Upload Hell -->
                        <div class="mb-4">
                            <h5>Logo für helles Design</h5>
                            <div class="mb-2">
                                <?php if (!empty($settings['logo_light'])): ?>
                                    <img src="<?= htmlspecialchars($logo_light) ?>" 
                                         alt="<?= htmlspecialchars($site_name) ?> Logo (Hell)" 
                                         class="img-thumbnail"
                                         style="max-height: 100px; max-width: 300px;">
                                <?php else: ?>
                                    <p class="text-muted">Kein Logo vorhanden</p>
                                <?php endif; ?>
                            </div>
                            <label for="logo_light" class="form-label">Neues Logo (Hell) hochladen</label>
                            <input type="file" class="form-control" id="logo_light" name="logo_light" accept="image/*">
                        </div>

                        <!-- Logo Upload Dunkel -->
                        <div class="mb-4">
                            <h5>Logo für dunkles Design</h5>
                            <div class="mb-2">
                                <?php if (!empty($settings['logo_dark'])): ?>
                                    <img src="<?= htmlspecialchars($logo_dark) ?>" 
                                         alt="<?= htmlspecialchars($site_name) ?> Logo (Dunkel)" 
                                         class="img-thumbnail bg-dark"
                                         style="max-height: 100px; max-width: 300px;">
                                <?php else: ?>
                                    <p class="text-muted">Kein Logo vorhanden</p>
                                <?php endif; ?>
                            </div>
                            <label for="logo_dark" class="form-label">Neues Logo (Dunkel) hochladen</label>
                            <input type="file" class="form-control" id="logo_dark" name="logo_dark" accept="image/*">
                        </div>
                        
                        <div class="form-text mb-4">Erlaubte Formate: JPEG, PNG, GIF. Maximale Größe: 5MB</div>
                        
                        <!-- Seitenname -->
                        <div class="mb-4">
                            <label for="site_name" class="form-label">Seitenname</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                   value="<?= htmlspecialchars($site_name) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                    </form>

                    <!-- Kassensturz-Formular -->
                    <form id="kassensturzForm" class="mt-4">
                        <h5>Kassensturz</h5>
                        <div class="row g-3">
                            <div class="col-auto">
                                <label for="kassensturz_datum" class="form-label">Datum</label>
                                <input type="date" class="form-control" id="kassensturz_datum" required>
                            </div>
                            <div class="col-auto">
                                <label for="kassensturz_betrag" class="form-label">Ist-Betrag (€)</label>
                                <input type="text" class="form-control" id="kassensturz_betrag" required>
                            </div>
                            <div class="col-auto">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Kassensturz durchführen</button>
                            </div>
                        </div>
                    </form>

                    <!-- Dynamische Spalten-Konfiguration -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Excel Import Konfiguration</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addColumnBtn">
                                <i class="bi bi-plus-circle"></i> Neue Spalte
                            </button>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="columnConfigForm">
                                <!-- Basis-Spalten (können nicht gelöscht werden) -->
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <h6>Pflichtfelder</h6>
                                    </div>
                                    <?php
                                    $required_columns = [
                                        'datum' => ['name' => 'Datum', 'type' => 'date'],
                                        'beleg' => ['name' => 'Beleg-Nr.', 'type' => 'text'],
                                        'bemerkung' => ['name' => 'Bemerkung', 'type' => 'text'],
                                        'einnahme' => ['name' => 'Einnahme', 'type' => 'decimal'],
                                        'ausgabe' => ['name' => 'Ausgabe', 'type' => 'decimal']
                                    ];
                                    
                                    foreach ($required_columns as $key => $config): ?>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label"><?= $config['name'] ?></label>
                                            <select name="columns[required][<?= $key ?>][excel_column]" class="form-select" required>
                                                <option value="">Spalte auswählen</option>
                                                <?php foreach (range('A', 'Z') as $column): ?>
                                                    <option value="<?= $column ?>" 
                                                        <?= ($settings['excel_mapping_' . $key] ?? '') === $column ? 'selected' : '' ?>>
                                                        Spalte <?= $column ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Dynamische Spalten -->
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <h6>Zusätzliche Spalten</h6>
                                    </div>
                                    <div id="dynamicColumns">
                                        <?php
                                        // Lade gespeicherte zusätzliche Spalten
                                        $custom_columns = json_decode($settings['custom_columns'] ?? '[]', true);
                                        foreach ($custom_columns as $column): ?>
                                            <div class="row g-3 mb-3 custom-column">
                                                <div class="col-md-3">
                                                    <label class="form-label">Spaltenname</label>
                                                    <input type="text" class="form-control" name="columns[custom][][name]" 
                                                           value="<?= htmlspecialchars($column['name']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Typ</label>
                                                    <select class="form-select" name="columns[custom][][type]" required>
                                                        <option value="text" <?= $column['type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                                        <option value="date" <?= $column['type'] === 'date' ? 'selected' : '' ?>>Datum</option>
                                                        <option value="decimal" <?= $column['type'] === 'decimal' ? 'selected' : '' ?>>Dezimalzahl</option>
                                                        <option value="integer" <?= $column['type'] === 'integer' ? 'selected' : '' ?>>Ganzzahl</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Excel-Spalte</label>
                                                    <select class="form-select" name="columns[custom][][excel_column]" required>
                                                        <option value="">Spalte auswählen</option>
                                                        <?php foreach (range('A', 'Z') as $excel_column): ?>
                                                            <option value="<?= $excel_column ?>" 
                                                                <?= $column['excel_column'] === $excel_column ? 'selected' : '' ?>>
                                                                Spalte <?= $excel_column ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="button" class="btn btn-danger d-block w-100 remove-column">
                                                        <i class="bi bi-trash"></i> Entfernen
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" name="save_column_config" class="btn btn-primary">Konfiguration speichern</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5"></div>

<?php require_once 'includes/footer.php'; ?> 

</body>
</html> 