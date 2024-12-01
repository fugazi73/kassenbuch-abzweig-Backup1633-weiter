<?php
// Backup-System
$base_path = dirname(dirname(__DIR__));
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/functions.php';
require_once $base_path . '/includes/auth.php';

// Prüfe Berechtigung
if (!check_login() || !is_admin()) {
    header('Location: ' . $base_path . '/index.php');
    exit;
}

// Backup-Verzeichnis
$backup_dir = $base_path . '/backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Backup erstellen
if (isset($_POST['create_backup'])) {
    try {
        $backup_type = $_POST['backup_type'] ?? 'full';
        $timestamp = date('Y-m-d_H-i-s');
        
        switch($backup_type) {
            case 'full':
                $filename = "backup_{$timestamp}_full.zip";
                createFullBackup($backup_dir . $filename);
                break;
                
            case 'db':
                $filename = "backup_{$timestamp}_db.zip";
                createDatabaseBackup($backup_dir . $filename);
                break;
                
            case 'files':
                $filename = "backup_{$timestamp}_files.zip";
                createFilesBackup($backup_dir . $filename);
                break;
        }
        
        // Backup in Datenbank registrieren
        $size = filesize($backup_dir . $filename);
        $stmt = $conn->prepare("INSERT INTO backups (filename, type, size, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ssi", $filename, $backup_type, $size);
        $stmt->execute();
        
        $_SESSION['success'] = 'Backup wurde erfolgreich erstellt.';
        
    } catch (Exception $e) {
        error_log("Backup Error: " . $e->getMessage());
        $_SESSION['error'] = 'Fehler beim Erstellen des Backups: ' . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Backup wiederherstellen
if (isset($_POST['restore_backup'])) {
    try {
        $backup_id = $_POST['backup_id'];
        
        // Hole Backup-Informationen
        $stmt = $conn->prepare("SELECT filename, type FROM backups WHERE id = ?");
        $stmt->bind_param("i", $backup_id);
        $stmt->execute();
        $backup = $stmt->get_result()->fetch_assoc();
        
        if (!$backup) {
            throw new Exception('Backup nicht gefunden');
        }
        
        $backup_file = $backup_dir . $backup['filename'];
        if (!file_exists($backup_file)) {
            throw new Exception('Backup-Datei nicht gefunden');
        }
        
        switch($backup['type']) {
            case 'full':
                restoreFullBackup($backup_file);
                break;
                
            case 'db':
                restoreDatabaseBackup($backup_file);
                break;
                
            case 'files':
                restoreFilesBackup($backup_file);
                break;
        }
        
        $_SESSION['success'] = 'Backup wurde erfolgreich wiederhergestellt.';
        
    } catch (Exception $e) {
        error_log("Restore Error: " . $e->getMessage());
        $_SESSION['error'] = 'Fehler beim Wiederherstellen des Backups: ' . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Backup löschen
if (isset($_POST['delete_backup'])) {
    try {
        $backup_id = $_POST['backup_id'];
        
        // Hole Backup-Informationen
        $stmt = $conn->prepare("SELECT filename FROM backups WHERE id = ?");
        $stmt->bind_param("i", $backup_id);
        $stmt->execute();
        $backup = $stmt->get_result()->fetch_assoc();
        
        if ($backup) {
            $backup_file = $backup_dir . $backup['filename'];
            if (file_exists($backup_file)) {
                unlink($backup_file);
            }
            
            // Aus Datenbank löschen
            $stmt = $conn->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->bind_param("i", $backup_id);
            $stmt->execute();
        }
        
        $_SESSION['success'] = 'Backup wurde erfolgreich gelöscht.';
        
    } catch (Exception $e) {
        error_log("Delete Backup Error: " . $e->getMessage());
        $_SESSION['error'] = 'Fehler beim Löschen des Backups: ' . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Hilfsfunktionen
function createFullBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Konnte ZIP-Datei nicht erstellen');
    }
    
    // Datenbank-Backup
    $db_backup = createDatabaseDump();
    $zip->addFromString('database.sql', $db_backup);
    
    // Dateien-Backup
    addFilesToZip($zip, __DIR__ . '/../../', 'backups/');
    
    $zip->close();
}

function createDatabaseBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Konnte ZIP-Datei nicht erstellen');
    }
    
    $db_backup = createDatabaseDump();
    $zip->addFromString('database.sql', $db_backup);
    
    $zip->close();
}

function createFilesBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Konnte ZIP-Datei nicht erstellen');
    }
    
    addFilesToZip($zip, __DIR__ . '/../../', 'backups/');
    
    $zip->close();
}

function createDatabaseDump() {
    global $conn;
    $output = '';
    
    // Tabellen auflisten
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Tabellen exportieren
    foreach ($tables as $table) {
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_row();
        $output .= "\n\n" . $row[1] . ";\n\n";
        
        $result = $conn->query("SELECT * FROM `$table`");
        while ($row = $result->fetch_assoc()) {
            $fields = array_map([$conn, 'real_escape_string'], $row);
            $output .= "INSERT INTO `$table` VALUES ('" . implode("','", $fields) . "');\n";
        }
    }
    
    return $output;
}

function addFilesToZip($zip, $path, $exclude = '') {
    $iterator = new RecursiveDirectoryIterator($path);
    $files = new RecursiveIteratorIterator($iterator);
    
    foreach ($files as $file) {
        if (!$file->isFile()) continue;
        
        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($path) + 1);
        
        // Exclude-Pfad überspringen
        if ($exclude && strpos($relative_path, $exclude) === 0) continue;
        
        $zip->addFile($file_path, $relative_path);
    }
}

function restoreFullBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename) !== true) {
        throw new Exception('Konnte Backup-Datei nicht öffnen');
    }
    
    // Datenbank wiederherstellen
    $db_content = $zip->getFromName('database.sql');
    if ($db_content !== false) {
        restoreDatabaseFromDump($db_content);
    }
    
    // Dateien wiederherstellen
    $extract_path = __DIR__ . '/../../';
    $zip->extractTo($extract_path);
    
    $zip->close();
}

function restoreDatabaseBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename) !== true) {
        throw new Exception('Konnte Backup-Datei nicht öffnen');
    }
    
    $db_content = $zip->getFromName('database.sql');
    if ($db_content === false) {
        throw new Exception('Keine Datenbank im Backup gefunden');
    }
    
    restoreDatabaseFromDump($db_content);
    
    $zip->close();
}

function restoreFilesBackup($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename) !== true) {
        throw new Exception('Konnte Backup-Datei nicht öffnen');
    }
    
    $extract_path = __DIR__ . '/../../';
    $zip->extractTo($extract_path);
    
    $zip->close();
}

function restoreDatabaseFromDump($dump) {
    global $conn;
    
    // Teile den Dump in einzelne Anweisungen
    $queries = explode(';', $dump);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if (!$conn->query($query)) {
            throw new Exception('Fehler beim Ausführen der SQL-Anweisung: ' . $conn->error);
        }
    }
}
?> 