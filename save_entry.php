<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Validierung
    if (!isset($_POST['datum']) || !isset($_POST['bemerkung'])) {
        throw new Exception('Fehlende Pflichtfelder');
    }

    $einnahme = floatval($_POST['einnahme'] ?? 0);
    $ausgabe = floatval($_POST['ausgabe'] ?? 0);
    $saldo = $einnahme - $ausgabe;

    $sql = "INSERT INTO kassenbuch_eintraege 
            (datum, beleg_nr, bemerkung, einnahme, ausgabe, saldo) 
            VALUES (?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddd", 
        $_POST['datum'],
        $_POST['beleg_nr'] ?? '',
        $_POST['bemerkung'],
        $einnahme,
        $ausgabe,
        $saldo
    );

    if ($stmt->execute()) {
        // Kassenstände neu berechnen
        update_kassenstande($conn);
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
