<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

if (!is_admin()) {
    handle_forbidden();
}

$von = $_GET['von'] ?? '';
$bis = $_GET['bis'] ?? '';
$format = $_GET['format'] ?? 'xlsx';

// Generiere eindeutigen Dateinamen
$filename = 'Kassenbuch_Export_' . date('Y-m-d_His') . '.' . $format;
$filepath = 'exports/' . $filename;

// Daten aus der Datenbank holen
$sql = "SELECT * FROM kassenbuch_eintraege WHERE datum BETWEEN ? AND ? ORDER BY datum ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $von, $bis);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Excel erstellen
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Datum');
$sheet->setCellValue('B1', 'Einnahme');
$sheet->setCellValue('C1', 'Ausgabe');
$sheet->setCellValue('D1', 'Bemerkung');

// Daten einfügen
$row = 2;
foreach ($data as $entry) {
    $sheet->setCellValue('A'.$row, $entry['datum']);
    $sheet->setCellValue('B'.$row, $entry['einnahme']);
    $sheet->setCellValue('C'.$row, $entry['ausgabe']);
    $sheet->setCellValue('D'.$row, $entry['bemerkung']);
    $row++;
}

// Speichere Datei
$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

// Speichere in Historie
$sql = "INSERT INTO export_history (date_from, date_to, format, filename, created_by) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", 
    $von,
    $bis,
    $format,
    $filename,
    $_SESSION['username']
);
$stmt->execute();

// Sende Datei zum Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
readfile($filepath);
exit;
  