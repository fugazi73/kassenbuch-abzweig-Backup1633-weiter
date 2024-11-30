<?php
require_once 'config.php';
require_once 'includes/init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    // Prüfe Admin-Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Prüfe ob Datei hochgeladen wurde
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine Datei oder Fehler beim Upload');
    }

    // Hole die Anzahl der Überschriftszeilen
    $header_rows = isset($_POST['header_rows']) ? (int)$_POST['header_rows'] : 1;
    $has_header = isset($_POST['has_header']) ? filter_var($_POST['has_header'], FILTER_VALIDATE_BOOLEAN) : true;

    // Lade Excel-Datei
    $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Starte Transaktion
    $conn->begin_transaction();

    $importCount = 0;
    $highestRow = $worksheet->getHighestRow();
    
    // Überspringe Header-Zeilen
    $startRow = $header_rows + 1;
    
    // Hole aktuellen Kassenstand
    $result = $conn->query("SELECT kassenstand FROM kassenbuch_eintraege 
                           WHERE bemerkung != 'Kassenstart'
                           ORDER BY datum DESC, id DESC LIMIT 1");
    $currentKassenstand = 0;
    if ($row = $result->fetch_assoc()) {
        $currentKassenstand = floatval($row['kassenstand']);
    }

    // Verarbeite jede Zeile
    for ($row = $startRow; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 'A'; $col <= 'G'; $col++) {
            $cell = $worksheet->getCell($col . $row);
            $rowData[$col] = $cell->getValue();
        }

        // Überspringe leere Zeilen oder Zeilen ohne Datum
        if (empty($rowData['B']) || trim($rowData['B']) === '') {
            continue;
        }

        // Formatiere Datum
        $datum = formatDate($rowData['B']);
        if (!$datum) {
            continue;
        }

        // Formatiere Beträge (entferne € und wandle , in .)
        $einnahme = 0;
        if (!empty($rowData['D'])) {
            $einnahme = str_replace(['.', ',', '€', ' '], ['', '.', '', ''], $rowData['D']);
            $einnahme = floatval($einnahme);
        }

        $ausgabe = 0;
        if (!empty($rowData['E'])) {
            $ausgabe = str_replace(['.', ',', '€', ' '], ['', '.', '', ''], $rowData['E']);
            $ausgabe = floatval($ausgabe);
        }

        // Berechne Saldo
        $saldo = $einnahme - $ausgabe;
        $currentKassenstand += $saldo;

        // Bereite Daten für Insert vor
        $data = [
            'datum' => $datum,
            'beleg_nr' => $rowData['A'],
            'bemerkung' => trim($rowData['C']),
            'einnahme' => $einnahme,
            'ausgabe' => $ausgabe,
            'saldo' => $saldo,
            'kassenstand' => $currentKassenstand,
            'user_id' => $_SESSION['user_id']
        ];

        // SQL für INSERT
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($data) - 1) . '?';

        $sql = "INSERT INTO kassenbuch_eintraege (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);

        // Bestimme die Typen für bind_param
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }

        // Dynamisches bind_param
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            throw new Exception('Fehler beim Einfügen der Daten: ' . $stmt->error);
        }
        
        $importCount++;
    }
    
    // Commit wenn alles erfolgreich
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$importCount Einträge wurden erfolgreich importiert"
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log('Import Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Hilfsfunktionen
function formatDate($value) {
    if (!$value) return null;
    
    // Versuche verschiedene Datumsformate
    $formats = [
        'd.m.Y',    // 15.01.2024
        'n/j/Y',    // 1/10/2024
        'Y-m-d'     // 2024-01-15
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date && $date->format($format) == $value) {
            return $date->format('Y-m-d');
        }
    }
    
    return null;
}