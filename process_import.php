<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Aktiviere Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definiere Log-Datei
$logFile = __DIR__ . '/import_log.txt';

// Debug-Log-Funktion
function debug_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] " . print_r($message, true) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    debug_log("=== Start Import ===");
    debug_log("POST Data: " . print_r($_POST, true));
    debug_log("FILES Data: " . print_r($_FILES, true));

    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine Datei oder Fehler beim Upload');
    }

    // Prüfe ob die Datei existiert und lesbar ist
    $inputFileName = $_FILES['excelFile']['tmp_name'];
    if (!is_readable($inputFileName)) {
        throw new Exception("Datei nicht lesbar: $inputFileName");
    }
    debug_log("Datei ist lesbar: $inputFileName");

    try {
        $spreadsheet = IOFactory::load($inputFileName);
        debug_log("Spreadsheet geladen");
    } catch (Exception $e) {
        throw new Exception("Fehler beim Laden der Excel-Datei: " . $e->getMessage());
    }

    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray(null, true, true, true);
    debug_log("Anzahl geladener Zeilen: " . count($rows));
    debug_log("Erste Zeile: " . print_r(reset($rows), true));

    // Überspringe Header
    if (isset($_POST['hasHeader']) && $_POST['hasHeader']) {
        array_shift($rows);
        debug_log("Header übersprungen");
    }

    $conn->begin_transaction();
    debug_log("Transaktion gestartet");

    // Hole den Kassenstart aus den Einstellungen
    $kassenstart_query = $conn->query("
        SELECT setting_value as betrag 
        FROM settings 
        WHERE setting_key = 'cash_start'
        LIMIT 1
    ");
    
    if ($kassenstart_row = $kassenstart_query->fetch_assoc()) {
        $laufender_kassenstand = floatval($kassenstart_row['betrag']);
        debug_log("Kassenstart aus Settings: " . $laufender_kassenstand);
    } else {
        $laufender_kassenstand = 0;
        debug_log("Kein Kassenstart in Settings gefunden, starte bei 0");
    }

    $importCount = 0;
    if (isset($_POST['import_excel'])) {
        try {
            // Lade Mapping-Konfiguration
            $mapping = [
                'datum' => $settings['excel_mapping_datum'] ?? 'B',
                'beleg' => $settings['excel_mapping_beleg'] ?? 'A',
                'bemerkung' => $settings['excel_mapping_bemerkung'] ?? 'C',
                'einnahme' => $settings['excel_mapping_einnahme'] ?? 'D',
                'ausgabe' => $settings['excel_mapping_ausgabe'] ?? 'E'
            ];

            $format = [
                'date' => $settings['excel_date_format'] ?? 'd.m.Y',
                'start_row' => intval($settings['excel_start_row'] ?? 2)
            ];

            // Verarbeite Excel-Datei mit Mapping
            foreach ($rows as $index => $row) {
                if ($index < $format['start_row'] - 1) continue;

                $datum = formatDate($row[$mapping['datum']], $format['date']);
                $beleg_nr = $row[$mapping['beleg']] ?? '';
                $bemerkung = $row[$mapping['bemerkung']] ?? '';
                $einnahme = parseAmount($row[$mapping['einnahme']] ?? '0');
                $ausgabe = parseAmount($row[$mapping['ausgabe']] ?? '0');

                // ... Rest der Import-Logik
            }

        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }

    $conn->commit();
    debug_log("Import erfolgreich: $importCount Einträge");
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => "$importCount Einträge erfolgreich importiert"
    ];

} catch (Exception $e) {
    debug_log("FEHLER: " . $e->getMessage());
    debug_log("Stack Trace: " . $e->getTraceAsString());
    
    if (isset($conn)) {
        $conn->rollback();
        debug_log("Transaktion zurückgerollt");
    }
    
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Fehler beim Import: ' . $e->getMessage()
    ];
}

header('Location: kassenbuch.php');
exit;

// Hilfsfunktionen
function formatDate($date, $format) {
    // Versucht verschiedene Datumsformate zu erkennen und zu konvertieren
    $timestamp = false;
    $formats = ['d.m.Y', 'Y-m-d', 'm/d/Y'];
    
    foreach ($formats as $fmt) {
        $d = DateTime::createFromFormat($fmt, $date);
        if ($d && $d->format($fmt) === $date) {
            $timestamp = $d->getTimestamp();
            break;
        }
    }
    
    return $timestamp ? date($format, $timestamp) : false;
}

function parseAmount($amount) {
    // Entfernt Währungssymbole und konvertiert in Float
    return floatval(preg_replace('/[^0-9,.-]/', '', str_replace(',', '.', $amount)));
}