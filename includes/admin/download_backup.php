<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../auth.php';

// Prüfe Berechtigung
if (!check_login() || !is_admin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Keine Berechtigung');
}

// Prüfe Parameter
$filename = $_GET['file'] ?? '';
if (empty($filename) || strpos($filename, '..') !== false) {
    header('HTTP/1.1 400 Bad Request');
    exit('Ungültiger Dateiname');
}

// Backup-Verzeichnis
$backup_dir = __DIR__ . '/../../backups/';
$file_path = $backup_dir . $filename;

// Prüfe ob Datei existiert
if (!file_exists($file_path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Backup nicht gefunden');
}

// Prüfe ob es sich um ein Backup handelt
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_(full|db|files)\.zip$/', $filename)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Ungültiger Backup-Name');
}

// Datei zum Download senden
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache');

readfile($file_path); 