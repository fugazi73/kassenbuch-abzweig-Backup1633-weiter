<?php
$file = 'public/templates/kassenbuch_vorlage.xlsx';

if (!file_exists($file)) {
    // Wenn die Datei nicht existiert, erstelle sie
    require 'create_template.php';
}

if (file_exists($file)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="kassenbuch_vorlage.xlsx"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($file);
    exit;
} else {
    die('Fehler beim Erstellen der Vorlage');
} 