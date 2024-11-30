<?php
require_once 'config.php';
require_once 'includes/init.php';

header('Content-Type: application/json');

try {
    // Starte Transaktion
    $conn->begin_transaction();

    // 1. Lösche das überflüssige Mapping
    $stmt = $conn->prepare("DELETE FROM settings WHERE setting_key = 'excel_mapping_beleg'");
    $stmt->execute();

    // 2. Prüfe ob die beleg-Spalte existiert
    $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE 'beleg'");
    
    if ($result->num_rows > 0) {
        // 3. Kopiere eventuell vorhandene Daten in beleg_nr, falls diese noch nicht gesetzt sind
        $conn->query("
            UPDATE kassenbuch_eintraege 
            SET beleg_nr = beleg 
            WHERE beleg_nr IS NULL AND beleg IS NOT NULL
        ");

        // 4. Entferne die überflüssige Spalte
        $conn->query("ALTER TABLE kassenbuch_eintraege DROP COLUMN beleg");
    }

    // Commit Transaktion
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Datenbank erfolgreich bereinigt'
    ]);

} catch (Exception $e) {
    // Rollback bei Fehler
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Fehler bei der Bereinigung: ' . $e->getMessage()
    ]);
} 