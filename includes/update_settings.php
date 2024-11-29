<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'chef'])) {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

if ($_POST['action'] === 'set_kassenstart') {
    try {
        $startdatum = $_POST['startdatum'];
        $startbetrag = floatval($_POST['startbetrag']);

        // Startbetrag in die Datenbank eintragen
        $sql = "INSERT INTO kassenbuch_eintraege 
                (datum, beleg_nr, bemerkung, einnahme, ausgabe, kassenstand) 
                VALUES (?, 'START', 'Kassenstart', ?, 0, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdd", $startdatum, $startbetrag, $startbetrag);
        
        if ($stmt->execute()) {
            // Alle nachfolgenden Kassenstände neu berechnen
            $sql = "UPDATE kassenbuch_eintraege 
                   SET kassenstand = (
                       SELECT SUM(einnahme - ausgabe) 
                       FROM (
                           SELECT einnahme, ausgabe 
                           FROM kassenbuch_eintraege 
                           WHERE datum <= ke.datum
                           ORDER BY datum, id
                       ) AS temp
                   )
                   FROM kassenbuch_eintraege ke
                   WHERE datum > ?
                   ORDER BY datum, id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $startdatum);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Fehler beim Speichern des Kassenstarts');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 