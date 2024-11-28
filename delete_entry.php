<?php
require_once 'config.php';

try {
    // Zu löschenden Eintrag abrufen
    $stmt = $conn->prepare("SELECT einnahme, ausgabe, datum FROM kassenbuch_eintraege WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $eintrag = $stmt->get_result()->fetch_assoc();
    
    $bewegung = $eintrag['einnahme'] - $eintrag['ausgabe'];

    // Transaktion starten
    $conn->begin_transaction();

    // Eintrag löschen
    $stmt = $conn->prepare("DELETE FROM kassenbuch_eintraege WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();

    // Alle nachfolgenden Kassenstände aktualisieren
    $stmt = $conn->prepare("UPDATE kassenbuch_eintraege 
                           SET kassenstand = kassenstand - ? 
                           WHERE datum > ? OR (datum = ? AND id > ?)
                           ORDER BY datum, id");
    $stmt->bind_param("dssi", 
        $bewegung,
        $eintrag['datum'],
        $eintrag['datum'],
        $_GET['id']
    );
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 