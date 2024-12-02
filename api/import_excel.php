<?php
// Erhöhe Speicher- und Zeitlimit für große Excel-Dateien
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
set_time_limit(300);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/permissions.php';

// Aktiviere Fehlerprotokollierung
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/import_errors.log');

header('Content-Type: application/json');

try {
    // Überprüfe Berechtigungen
    if (!check_permission('import')) {
        throw new Exception('Keine Berechtigung für den Import');
    }

    // Validiere Request
    if (!isset($_POST['mapping']) || !is_array($_POST['mapping'])) {
        throw new Exception('Keine gültige Spaltenzuordnung gefunden');
    }

    $mapping = $_POST['mapping'];
    $required_fields = ['datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe'];
    
    foreach ($required_fields as $field) {
        if (!isset($mapping[$field])) {
            throw new Exception("Pflichtfeld '$field' fehlt in der Spaltenzuordnung");
        }
    }

    // Hole die Excel-Daten
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine gültige Excel-Datei gefunden');
    }

    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Optimierte Excel-Verarbeitung
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($_FILES['excel_file']['tmp_name']);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    
    $spreadsheet = $reader->load($_FILES['excel_file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray(null, true, true, true);

    // Entferne Header-Zeile wenn ausgewählt
    if (isset($_POST['has_header']) && $_POST['has_header'] === 'on') {
        $headerRow = isset($_POST['header_row']) ? (int)$_POST['header_row'] : 1;
        for ($i = 0; $i < $headerRow; $i++) {
            array_shift($data);
        }
    }

    $conn->begin_transaction();
    
    $successful_imports = 0;
    $errors = [];
    $skipped = 0;

    // Verarbeite die Daten
    foreach ($data as $row_index => $row) {
        try {
            // Überspringe leere Zeilen
            if (empty(array_filter($row))) {
                continue;
            }

            // Prüfe ob es sich um eine Summen- oder Saldo-Zeile handelt
            $bemerkung_value = trim(strtolower($row[$mapping['bemerkung']] ?? ''));
            if (empty($bemerkung_value)) {
                continue;
            }

            if (in_array($bemerkung_value, ['summen', 'saldo', 'summe', 'gesamt']) || 
                strpos($bemerkung_value, 'summ') !== false || 
                strpos($bemerkung_value, 'sald') !== false ||
                strpos($bemerkung_value, 'gesamt') !== false) {
                $skipped++;
                continue;
            }

            // Validiere und formatiere das Datum
            $datum = null;
            $raw_datum = trim($row[$mapping['datum']] ?? '');
            
            if (empty($raw_datum)) {
                continue; // Überspringe Zeilen ohne Datum
            }

            if ($raw_datum instanceof \DateTime) {
                $datum = $raw_datum->format('Y-m-d');
            } else {
                // Versuche verschiedene Datumsformate
                $possibleDate = str_replace('.', '-', $raw_datum);
                $timestamp = strtotime($possibleDate);
                if ($timestamp === false) {
                    throw new Exception("Ungültiges Datumsformat in Zeile " . ($row_index + 1));
                }
                $datum = date('Y-m-d', $timestamp);
            }

            $beleg_nr = trim($row[$mapping['beleg_nr']] ?? '');
            $bemerkung = trim($row[$mapping['bemerkung']] ?? '');
            
            // Konvertiere Zahlen und bereinige sie
            $einnahme_raw = trim(str_replace(['€', ' ', '.'], '', $row[$mapping['einnahme']] ?? '0'));
            $ausgabe_raw = trim(str_replace(['€', ' ', '.'], '', $row[$mapping['ausgabe']] ?? '0'));
            
            // Ersetze Komma durch Punkt und entferne Tausendertrennzeichen
            $einnahme = floatval(str_replace(',', '.', $einnahme_raw));
            $ausgabe = floatval(str_replace(',', '.', $ausgabe_raw));

            // Überspringe Zeilen die offensichtlich Summen sind (hohe Beträge)
            if ($einnahme > 10000 || $ausgabe > 10000) {
                $skipped++;
                continue;
            }

            $sql = "INSERT INTO kassenbuch (datum, beleg_nr, bemerkung, einnahme, ausgabe) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL-Fehler: " . $conn->error);
            }
            
            $stmt->bind_param('sssdd', $datum, $beleg_nr, $bemerkung, $einnahme, $ausgabe);
            
            if (!$stmt->execute()) {
                throw new Exception("Fehler beim Einfügen: " . $stmt->error);
            }

            $successful_imports++;
            
        } catch (Exception $e) {
            error_log("Import Fehler in Zeile $row_index: " . $e->getMessage());
            $errors[] = "Zeile " . ($row_index + 1) . ": " . $e->getMessage();
        }
    }

    // Speicher freigeben
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    if (count($errors) > 0 && $successful_imports === 0) {
        // Wenn keine erfolgreichen Importe und nur Fehler, rolle zurück
        $conn->rollback();
        throw new Exception("Import fehlgeschlagen: " . implode(", ", $errors));
    }

    $conn->commit();

    $message = "Import erfolgreich: $successful_imports Einträge importiert";
    if ($skipped > 0) {
        $message .= ", $skipped Summen-/Saldo-Zeilen übersprungen";
    }
    if (count($errors) > 0) {
        $message .= "\nWarnungen: " . implode("\n", $errors);
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    error_log("Kritischer Import-Fehler: " . $e->getMessage());
    
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    http_response_code(200); // Setze HTTP-Status auf 200 statt 500
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 