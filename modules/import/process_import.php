<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    // Prüfe Berechtigung
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Hole POST-Daten
    $postData = json_decode(file_get_contents('php://input'), true);
    if (!$postData || !isset($postData['mapping'])) {
        throw new Exception('Ungültige Anfrage');
    }

    $mapping = $postData['mapping'];
    $requiredColumns = ['datum', 'beleg', 'beschreibung', 'einnahme', 'ausgabe'];
    
    // Prüfe ob alle erforderlichen Spalten zugeordnet sind
    foreach ($requiredColumns as $column) {
        if (!isset($mapping[$column])) {
            throw new Exception("Spalte '$column' wurde nicht zugeordnet");
        }
    }

    // Lade die temporäre Excel-Datei
    if (!isset($_SESSION['import_file']) || !file_exists($_SESSION['import_file'])) {
        throw new Exception('Keine Datei zum Importieren gefunden');
    }

    $spreadsheet = IOFactory::load($_SESSION['import_file']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Starte Transaktion
    $db->beginTransaction();
    
    try {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        // Iteriere über alle Zeilen (ab Zeile 2, da Zeile 1 die Überschriften enthält)
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = trim($cell->getValue());
            }
            
            // Überspringe leere Zeilen
            if (empty(array_filter($rowData))) {
                continue;
            }
            
            // Bereite Daten vor
            $entry = [
                'datum' => formatDate($rowData[$mapping['datum']]),
                'beleg_nr' => $rowData[$mapping['beleg']],
                'beschreibung' => $rowData[$mapping['beschreibung']],
                'einnahme' => normalizeAmount($rowData[$mapping['einnahme']]),
                'ausgabe' => normalizeAmount($rowData[$mapping['ausgabe']]),
                'user_id' => $_SESSION['user_id']
            ];
            
            // Validiere Daten
            if (!$entry['datum']) {
                $errorCount++;
                $errors[] = "Zeile {$row->getRowIndex()}: Ungültiges Datum";
                continue;
            }
            
            // Füge Eintrag hinzu
            $stmt = $db->prepare("
                INSERT INTO kassenbuch 
                (datum, beleg_nr, beschreibung, einnahme, ausgabe, user_id, created_at) 
                VALUES 
                (:datum, :beleg_nr, :beschreibung, :einnahme, :ausgabe, :user_id, NOW())
            ");
            
            if ($stmt->execute($entry)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Zeile {$row->getRowIndex()}: Fehler beim Speichern";
            }
        }
        
        // Wenn alles erfolgreich war, commit die Transaktion
        $db->commit();
        
        // Lösche temporäre Datei
        if (file_exists($_SESSION['import_file'])) {
            unlink($_SESSION['import_file']);
            unset($_SESSION['import_file']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Import abgeschlossen: $successCount Einträge importiert, $errorCount Fehler",
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Import Fehler: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Hilfsfunktionen
function formatDate($date) {
    if (empty($date)) return false;
    
    // Versuche verschiedene Datumsformate
    $formats = [
        'd.m.Y', 'Y-m-d', 'd/m/Y', 
        'd.m.y', 'y-m-d', 'd/m/y'
    ];
    
    foreach ($formats as $format) {
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) == $date) {
            return $d->format('Y-m-d');
        }
    }
    
    return false;
}

function normalizeAmount($amount) {
    if (empty($amount)) return 0.00;
    
    // Entferne Währungssymbole und Tausendertrennzeichen
    $amount = preg_replace('/[^0-9,.-]/', '', $amount);
    
    // Ersetze Komma durch Punkt
    $amount = str_replace(',', '.', $amount);
    
    // Konvertiere zu Float und runde auf 2 Dezimalstellen
    return round(floatval($amount), 2);
} 