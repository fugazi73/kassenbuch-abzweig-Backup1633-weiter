<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können den Kassenstart ändern.'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->begin_transaction();

        $datum = $_POST['datum'];
        $betrag = floatval(str_replace(',', '.', $_POST['betrag']));

        // 1. Speichere in settings Tabelle
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                               VALUES ('cash_start', ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $betrag, $betrag);
        
        if (!$stmt->execute()) {
            throw new Exception("Fehler beim Speichern des Kassenstarts in Settings");
        }

        // 2. Lösche alten Kassenstart falls vorhanden
        $conn->query("DELETE FROM kassenbuch_eintraege WHERE bemerkung = 'Kassenstart'");

        // 3. Erstelle neuen Kassenstart-Eintrag
        $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege 
            (datum, bemerkung, einnahme, ausgabe, kassenstand) 
            VALUES (?, 'Kassenstart', ?, 0, ?)");
        
        $stmt->bind_param("sdd", $datum, $betrag, $betrag);
        
        if (!$stmt->execute()) {
            throw new Exception("Fehler beim Speichern des Kassenstarts im Kassenbuch");
        }

        // 4. Hole alle Einträge nach dem Kassenstart
        $stmt = $conn->prepare("SELECT id, einnahme, ausgabe 
                               FROM kassenbuch_eintraege 
                               WHERE datum >= ? 
                               ORDER BY datum ASC, id ASC");
        $stmt->bind_param("s", $datum);
        $stmt->execute();
        $result = $stmt->get_result();

        // 5. Berechne neue Kassenstände
        $laufender_kassenstand = $betrag;
        $updates = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['id'] != $stmt->insert_id) { // Überspringe den Kassenstart-Eintrag
                $laufender_kassenstand += $row['einnahme'] - $row['ausgabe'];
                $updates[] = "WHEN " . $row['id'] . " THEN " . $laufender_kassenstand;
            }
        }

        // 6. Führe Update aus
        if (!empty($updates)) {
            $sql = "UPDATE kassenbuch_eintraege 
                    SET kassenstand = CASE id 
                        " . implode("\n", $updates) . "
                    END 
                    WHERE datum >= ? AND bemerkung != 'Kassenstart'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $datum);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Kassenstart wurde erfolgreich gespeichert'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 