<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

try {
    // Ursprünglichen Eintrag abrufen
    $stmt = $conn->prepare("SELECT einnahme, ausgabe, datum FROM kassenbuch_eintraege WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    $alter_eintrag = $stmt->get_result()->fetch_assoc();
    
    // Neue Werte vorbereiten
    $neue_einnahme = floatval($_POST['einnahme'] ?? 0);
    $neue_ausgabe = floatval($_POST['ausgabe'] ?? 0);
    
    // Transaktion starten
    $conn->begin_transaction();

    // Eintrag aktualisieren
    $stmt = $conn->prepare("UPDATE kassenbuch_eintraege 
                           SET datum = ?, 
                               beleg = ?, 
                               bemerkung = ?, 
                               einnahme = ?, 
                               ausgabe = ? 
                           WHERE id = ?");
                           
    $stmt->bind_param("ssdddi", 
        $_POST['datum'],
        $_POST['beleg'],
        $_POST['bemerkung'],
        $neue_einnahme,
        $neue_ausgabe,
        $_POST['id']
    );
    $stmt->execute();

    // Alle Kassenstände neu berechnen
    $sql = "UPDATE kassenbuch_eintraege 
            SET kassenstand = (
                SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0)
                FROM kassenbuch_eintraege AS vorherige
                WHERE vorherige.datum <= kassenbuch_eintraege.datum 
                AND (vorherige.datum < kassenbuch_eintraege.datum 
                     OR vorherige.id <= kassenbuch_eintraege.id)
            )
            WHERE datum >= ?
            ORDER BY datum, id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_POST['datum']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Fehler beim Aktualisieren des Eintrags: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Aktualisieren: ' . $e->getMessage()
    ]);
} 