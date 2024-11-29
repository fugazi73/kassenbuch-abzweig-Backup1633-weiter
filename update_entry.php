<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Validierung
    if (!isset($_POST['id']) || !isset($_POST['datum']) || !isset($_POST['bemerkung'])) {
        throw new Exception('Fehlende Pflichtfelder');
    }

    // Saldo berechnen
    $einnahme = floatval($_POST['einnahme'] ?? 0);
    $ausgabe = floatval($_POST['ausgabe'] ?? 0);
    $saldo = $einnahme - $ausgabe;

    $sql = "UPDATE kassenbuch_eintraege 
            SET datum = ?, 
                beleg_nr = ?, 
                bemerkung = ?, 
                einnahme = ?, 
                ausgabe = ?,
                saldo = ?
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdddi", 
        $_POST['datum'],
        $_POST['beleg_nr'],
        $_POST['bemerkung'],
        $einnahme,
        $ausgabe,
        $saldo,
        $_POST['id']
    );

    if ($stmt->execute()) {
        // Alle nachfolgenden Kassenstände neu berechnen
        $update_sql = "
            UPDATE kassenbuch_eintraege ke1
            JOIN (
                SELECT id, 
                       @running_total := @running_total + (einnahme - ausgabe) as new_kassenstand
                FROM kassenbuch_eintraege, 
                     (SELECT @running_total := (
                         SELECT wert FROM einstellungen WHERE name = 'startbetrag'
                     )) vars
                WHERE datum >= ?
                ORDER BY datum ASC, id ASC
            ) ke2 ON ke1.id = ke2.id
            SET ke1.kassenstand = ke2.new_kassenstand
            WHERE ke1.datum >= ?
        ";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $_POST['datum'], $_POST['datum']);
        $update_stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Fehler beim Speichern');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 