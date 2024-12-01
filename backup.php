<?php
session_start();
require_once 'includes/init.php';  // Lädt die Settings-Variablen
require_once 'config.php';
require_once 'functions.php';

// Prüfe Berechtigungen
if (!is_admin()) {
    handle_forbidden();
}

// API-Endpunkt für AJAX-Anfragen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'backup':
                $type = $_POST['type'] ?? 'full';
                $result = createBackup($type);
                echo json_encode($result);
                exit;
                
            case 'delete':
                if (isset($_POST['file'])) {
                    $file = __DIR__ . '/backups/' . basename($_POST['file']);
                    if (file_exists($file) && unlink($file)) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Datei konnte nicht gelöscht werden']);
                    }
                }
                exit;
                
            case 'restore':
                if (isset($_POST['file'])) {
                    $result = restoreBackup('backups/' . basename($_POST['file']));
                    echo json_encode($result);
                }
                exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
    exit;
}

// Normale Seitenanzeige
$page_title = $site_name ? "Backup & Restore - " . htmlspecialchars($site_name) : "Backup & Restore";
include 'includes/header.php';

// Funktion zum Erstellen des Datenbank-Backups
function createDatabaseBackup($filename) {
    global $conn;
    
    try {
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $output = "";
        
        // Für jede Tabelle
        foreach ($tables as $table) {
            $result = $conn->query("SELECT * FROM `$table`");
            $numFields = $result->field_count;
            
            // Tabelle löschen + neu erstellen
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $row2 = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
            $output .= $row2[1] . ";\n\n";
            
            // Daten einfügen
            while ($row = $result->fetch_row()) {
                $output .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $output .= '"' . $row[$j] . '"';
                    } else {
                        $output .= '""';
                    }
                    if ($j < ($numFields - 1)) {
                        $output .= ',';
                    }
                }
                $output .= ");\n";
            }
            $output .= "\n\n";
        }
        
        file_put_contents($filename, $output);
        return true;
    } catch (Exception $e) {
        error_log("Database Backup Error: " . $e->getMessage());
        return false;
    }
}

// Funktion zum Erstellen des Datei-Backups
function createFilesBackup($filename) {
    try {
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Liste der wichtigen Verzeichnisse und Dateien
            $important_paths = [
                'js/',
                'includes/',
                'styles/',
                'assets/',
                'images/',
                'config.php',
                'functions.php',
                'index.php',
                'kassenbuch.php',
                'settings.php',
                'import_excel.php',
                'export.php',
                'save_kassenstart.php',
                'save_entry.php',
                'delete_entry.php',
                'update_entry.php',
                'save_logo.php',
                'save_site_settings.php'
            ];

            foreach ($important_paths as $path) {
                $fullPath = __DIR__ . '/' . $path;
                
                if (is_dir($fullPath)) {
                    // Wenn es ein Verzeichnis ist, füge alle Dateien darin hinzu
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($fullPath),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen(__DIR__) + 1);
                            
                            // Ausschließen von temporären und versteckten Dateien
                            if (!preg_match('/(^|\/)(\.|\.\.|temp|\.git|\.DS_Store|Thumbs\.db)/i', $relativePath)) {
                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                    }
                } elseif (file_exists($fullPath)) {
                    // Wenn es eine einzelne Datei ist
                    $zip->addFile($fullPath, basename($fullPath));
                }
            }
            
            $zip->close();
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Files Backup Error: " . $e->getMessage());
        return false;
    }
}

// Hauptfunktion zum Erstellen des Backups
function createBackup($type = 'full') {
    try {
        // Absoluten Pfad zum Backup-Verzeichnis erstellen
        $backup_dir = __DIR__ . '/backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        // Zeitstempel für Backup-Namen
        $date = date('Y-m-d_H-i-s');
        $backup_path = $backup_dir . '/temp_' . $date;
        mkdir($backup_path);

        $success = true;
        $error_message = '';

        // Datenbank-Backup
        if ($type === 'full' || $type === 'db') {
            $sql_file = $backup_path . '/database.sql';
            if (!createDatabaseBackup($sql_file)) {
                $success = false;
                $error_message = 'Fehler beim Erstellen des Datenbank-Backups';
            }
        }

        // Dateien-Backup
        if ($success && ($type === 'full' || $type === 'files')) {
            $files_zip = $backup_path . '/files.zip';
            if (!createFilesBackup($files_zip)) {
                $success = false;
                $error_message = 'Fehler beim Erstellen des Datei-Backups';
            }
        }

        // Finales ZIP erstellen
        if ($success) {
            $final_zip = new ZipArchive();
            $final_zip_file = $backup_dir . '/backup_' . $date . '_' . $type . '.zip';
            
            if ($final_zip->open($final_zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                if ($type === 'full' || $type === 'db') {
                    $final_zip->addFile($backup_path . '/database.sql', 'database.sql');
                }
                if ($type === 'full' || $type === 'files') {
                    $final_zip->addFile($backup_path . '/files.zip', 'files.zip');
                }
                $final_zip->close();
            } else {
                $success = false;
                $error_message = 'Fehler beim Erstellen des finalen ZIP-Archives';
            }
        }

        // Temporäre Dateien aufräumen
        if (file_exists($backup_path . '/database.sql')) {
            unlink($backup_path . '/database.sql');
        }
        if (file_exists($backup_path . '/files.zip')) {
            unlink($backup_path . '/files.zip');
        }
        rmdir($backup_path);

        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $error_message];
        }

    } catch (Exception $e) {
        error_log("Backup Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Restore-Funktion
function restoreBackup($backup_file) {
    global $conn;
    
    try {
        // Absoluten Pfad zum Backup verwenden
        $backup_file = __DIR__ . '/' . $backup_file;
        if (!file_exists($backup_file)) {
            throw new Exception('Backup-Datei nicht gefunden');
        }

        $temp_dir = __DIR__ . '/temp_restore_' . time();
        mkdir($temp_dir);

        // Backup entpacken
        $zip = new ZipArchive();
        if ($zip->open($backup_file) === TRUE) {
            $zip->extractTo($temp_dir);
            $zip->close();
        }

        // Datenbank wiederherstellen
        $sql = file_get_contents($temp_dir . '/database.sql');
        $conn->multi_query($sql);
        while ($conn->more_results() && $conn->next_result());

        // Dateien wiederherstellen
        $zip = new ZipArchive();
        if ($zip->open($temp_dir . '/files.zip') === TRUE) {
            $zip->extractTo(__DIR__);
            $zip->close();
        }

        // Aufräumen
        array_map('unlink', glob("$temp_dir/*"));
        rmdir($temp_dir);

        return ['success' => true];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Funktion zum Auflisten der Backups anpassen
function getBackups() {
    // Absoluten Pfad zum Backup-Verzeichnis erstellen
    $backup_dir = __DIR__ . '/backups';
    
    // Prüfe ob Verzeichnis existiert, wenn nicht, erstelle es
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
        return [];
    }
    
    // Hole alle Backup-Dateien
    $backups = glob($backup_dir . '/backup_*.zip');
    $backup_list = [];
    
    foreach ($backups as $backup) {
        // Extrahiere den Typ aus dem Dateinamen
        preg_match('/_([^_]+)\.zip$/', $backup, $matches);
        $type = isset($matches[1]) ? $matches[1] : 'full';
        
        // Typ in lesbares Format umwandeln
        $type_text = '';
        switch($type) {
            case 'full':
                $type_text = 'Vollständig';
                break;
            case 'db':
                $type_text = 'Datenbank';
                break;
            case 'files':
                $type_text = 'Dateien';
                break;
            default:
                $type_text = 'Unbekannt';
        }
        
        $backup_list[] = [
            'file' => basename($backup),
            'date' => filemtime($backup),
            'size' => filesize($backup),
            'type' => $type_text
        ];
    }
    
    // Sortiere nach Datum (neueste zuerst)
    usort($backup_list, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backup_list;
}

// Funktion zum Löschen alter Backups
function cleanupOldBackups($days_to_keep) {
    $backup_dir = __DIR__ . '/backups';
    $cutoff = time() - ($days_to_keep * 24 * 60 * 60);
    
    foreach (glob($backup_dir . '/backup_*.zip') as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
        }
    }
}

// Hole aktuelle Backups
$backups = getBackups();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-archive"></i> System Backup & Restore</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#backupModal">
                        <i class="bi bi-plus-circle"></i> Neues Backup erstellen
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Dateiname</th>
                                    <th>Typ</th>
                                    <th>Größe</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?= date('d.m.Y H:i', $backup['date']) ?></td>
                                    <td><?= htmlspecialchars($backup['file']) ?></td>
                                    <td><?= $backup['type'] ?></td>
                                    <td><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="backups/<?= urlencode($backup['file']) ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Herunterladen">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-warning restore-backup" 
                                                    data-file="<?= htmlspecialchars($backup['file']) ?>"
                                                    title="Wiederherstellen">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-backup"
                                                    data-file="<?= htmlspecialchars($backup['file']) ?>"
                                                    title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Backup-Erstellung -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neues Backup erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="backupForm">
                    <div class="mb-3">
                        <label class="form-label">Backup-Typ</label>
                        <select name="type" class="form-select" required>
                            <option value="full">Vollständiges Backup (Datenbank & Dateien)</option>
                            <option value="db">Nur Datenbank</option>
                            <option value="files">Nur Dateien</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="createBackup">Backup erstellen</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createBackup').addEventListener('click', async () => {
    const form = document.getElementById('backupForm');
    const formData = new FormData(form);
    formData.append('action', 'backup');
    
    try {
        const button = document.getElementById('createBackup');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Backup wird erstellt...';
        
        const response = await fetch('backup.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Ungültige Server-Antwort: ' + responseText);
        }
        
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('backupModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler beim Erstellen des Backups'));
        }
    } catch (error) {
        console.error('Backup Error:', error);
        alert('Fehler beim Erstellen des Backups: ' + error.message);
    } finally {
        const button = document.getElementById('createBackup');
        button.disabled = false;
        button.innerHTML = 'Backup erstellen';
    }
});

// Restore-Funktion
document.querySelectorAll('.restore-backup').forEach(button => {
    button.addEventListener('click', async (e) => {
        const file = e.target.closest('button').dataset.file;
        if (!confirm('Warnung: Dies wird alle aktuellen Daten überschreiben! Fortfahren?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('file', file);
            
            const response = await fetch('backup.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                alert('System wurde erfolgreich wiederhergestellt!');
                location.reload();
            } else {
                alert('Fehler: ' + data.error);
            }
        } catch (error) {
            alert('Fehler bei der Wiederherstellung');
        }
    });
});

// Delete-Funktion
document.querySelectorAll('.delete-backup').forEach(button => {
    button.addEventListener('click', async (e) => {
        const file = e.target.closest('button').dataset.file;
        if (!confirm('Möchten Sie dieses Backup wirklich löschen?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('file', file);
            
            const response = await fetch('backup.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler: ' + data.error);
            }
        } catch (error) {
            alert('Fehler beim Löschen');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
