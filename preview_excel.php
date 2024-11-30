<?php
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'config.php';
require_once 'includes/init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Buffer starten
ob_start();

// Hilfsfunktion für JSON-Antworten
function sendJsonResponse($success, $data = null, $message = null) {
    ob_clean(); // Buffer leeren
    $response = ['success' => $success];
    if ($data !== null) $response['preview'] = $data;
    if ($message !== null) $response['message'] = $message;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Stelle sicher, dass keine Ausgaben vor dem Header geschehen
    ob_clean();
    header('Content-Type: application/json');

    // Debug-Logging
    error_log("=== Start Excel-Vorschau ===");
    error_log("POST-Daten: " . print_r($_POST, true));
    error_log("Datei-Informationen: " . print_r($_FILES, true));

    // Prüfe Admin-Berechtigung
    if (!function_exists('is_admin')) {
        error_log("Funktion is_admin() nicht gefunden");
        sendJsonResponse(false, null, 'Systemfehler: Berechtigungsprüfung nicht möglich');
    }

    if (!is_admin()) {
        error_log("Keine Admin-Berechtigung");
        sendJsonResponse(false, null, 'Keine Berechtigung');
    }

    // Prüfe ob Datei hochgeladen wurde
    if (!isset($_FILES['excel_file'])) {
        error_log("Keine Datei im Request gefunden");
        sendJsonResponse(false, null, 'Keine Datei gefunden');
    }

    // Prüfe Upload-Fehler
    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'Die hochgeladene Datei überschreitet die upload_max_filesize Direktive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Die hochgeladene Datei überschreitet die MAX_FILE_SIZE Direktive im HTML Formular',
            UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen',
            UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei hochgeladen',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporärer Ordner fehlt',
            UPLOAD_ERR_CANT_WRITE => 'Fehler beim Schreiben der Datei',
            UPLOAD_ERR_EXTENSION => 'Eine PHP-Erweiterung hat den Upload gestoppt'
        ];
        $errorMessage = $uploadErrors[$_FILES['excel_file']['error']] ?? 'Unbekannter Upload-Fehler';
        error_log("Upload-Fehler: " . $errorMessage);
        sendJsonResponse(false, null, $errorMessage);
    }

    // Prüfe Datei
    $tmpFile = $_FILES['excel_file']['tmp_name'];
    $mimeType = $_FILES['excel_file']['type'];
    error_log("Datei-Typ: $mimeType");
    error_log("Temporäre Datei: $tmpFile");

    if (!file_exists($tmpFile)) {
        error_log("Temporäre Datei existiert nicht: $tmpFile");
        sendJsonResponse(false, null, 'Temporäre Datei nicht gefunden');
    }

    if (!is_readable($tmpFile)) {
        error_log("Temporäre Datei nicht lesbar: $tmpFile");
        sendJsonResponse(false, null, 'Temporäre Datei nicht lesbar');
    }

    // Versuche die Excel-Datei zu laden
    try {
        error_log("Versuche Excel-Datei zu laden...");
        $spreadsheet = IOFactory::load($tmpFile);
        error_log("Excel-Datei erfolgreich geladen");
    } catch (Exception $e) {
        error_log("Fehler beim Laden der Excel-Datei: " . $e->getMessage());
        sendJsonResponse(false, null, 'Fehler beim Lesen der Excel-Datei: ' . $e->getMessage());
    }

    // Hole die Anzahl der Überschriftszeilen
    $header_rows = isset($_POST['header_rows']) ? (int)$_POST['header_rows'] : 1;
    $has_header = isset($_POST['has_header']) ? filter_var($_POST['has_header'], FILTER_VALIDATE_BOOLEAN) : true;
    
    error_log("Anzahl Überschriftszeilen: " . $header_rows);
    error_log("Hat Überschriften: " . ($has_header ? 'ja' : 'nein'));

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($tmpFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Hole die Zeilen für die Vorschau
    $preview = [];
    $highestRow = min($worksheet->getHighestRow(), $header_rows + 5); // Header + 5 Datenzeilen
    $highestColumn = $worksheet->getHighestColumn();
    
    error_log("Höchste Zeile: $highestRow");
    error_log("Höchste Spalte: $highestColumn");

    // Konvertiere Buchstaben-Spalte in Zahl (A=1, B=2, etc.)
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    // Spaltenzuordnung
    $columnMapping = [
        'Beleg #' => 'beleg_nr',
        'Datum' => 'datum',
        'Bemerkung' => 'bemerkung',
        'Einnahme' => 'einnahme',
        'Ausgabe' => 'ausgabe',
        'Saldo' => 'saldo'
    ];

    // Hole alle Zeilen (inkl. Überschriften)
    $allRows = [];
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col - 1);
            $cell = $worksheet->getCell($colLetter . $row);
            
            // Hole den berechneten Wert statt der Formel
            if ($cell->getDataType() == DataType::TYPE_FORMULA) {
                try {
                    $value = $cell->getCalculatedValue();
                } catch (Exception $e) {
                    error_log("Fehler bei Formelberechnung: " . $e->getMessage());
                    $value = "FEHLER";
                }
            } else {
                $value = $cell->getValue();
            }
            
            // Formatiere Zahlen
            if ($cell->getDataType() == DataType::TYPE_NUMERIC || 
                (is_numeric($value) && !empty($value))) {
                if (Date::isDateTime($cell)) {
                    $value = Date::excelToDateTimeObject($value)->format('d.m.Y');
                } else {
                    // Prüfe ob es sich um einen Geldbetrag handelt
                    $style = $cell->getStyle();
                    $numberFormat = $style->getNumberFormat()->getFormatCode();
                    if (strpos($numberFormat, '€') !== false || 
                        strpos($numberFormat, '#,##0.00') !== false) {
                        $value = number_format($value, 2, ',', '.');
                    }
                }
            }
            
            $rowData[] = $value;
        }
        $allRows[] = $rowData;
    }

    // Extrahiere die Überschriftszeilen
    $headerRows = array_slice($allRows, 0, $header_rows);

    // Bestimme die Spaltenüberschriften
    if ($has_header) {
        // Verwende die letzte Überschriftszeile für die Spaltennamen
        $headerRow = end($headerRows);
    } else {
        // Generiere Standard-Spaltennamen (A, B, C, ...)
        $headerRow = array_map(function($i) {
            return Coordinate::stringFromColumnIndex($i);
        }, range(0, $highestColumnIndex - 1));
    }

    // Erstelle die Spalteninformationen
    $columns = [];
    foreach ($headerRow as $index => $value) {
        // Versuche den Spaltennamen zu normalisieren
        $normalizedValue = trim(str_replace(['#', '€', '.', ','], '', $value));
        
        // Finde die passende Datenbankspalte
        $dbColumn = $columnMapping[$normalizedValue] ?? $normalizedValue;
        
        $columns[] = [
            'name' => $value ?: 'Spalte ' . Coordinate::stringFromColumnIndex($index),
            'db_column' => $dbColumn
        ];
    }

    // Extrahiere die Datenzeilen (nach den Überschriften)
    $dataRows = array_slice($allRows, $header_rows);

    error_log("Vorschau erstellt: " . count($columns) . " Spalten, " . count($dataRows) . " Zeilen");
    
    // Sende erfolgreiche Antwort
    sendJsonResponse(true, [
        'header_rows' => $headerRows,
        'columns' => array_column($columns, 'name'),
        'db_columns' => array_column($columns, 'db_column'),
        'rows' => $dataRows
    ]);

} catch (Exception $e) {
    error_log("Kritischer Fehler: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    sendJsonResponse(false, null, 'Ein Fehler ist aufgetreten: ' . $e->getMessage());
} 