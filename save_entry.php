<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->begin_transaction();

    // Eintrag speichern
    $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege (datum, bemerkung, einnahme, ausgabe) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", 
        $data['datum'],
        $data['bemerkung'],
        $data['einnahme'],
        $data['ausgabe']
    );
    $stmt->execute();

    // Kassenstände aktualisieren
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
    $stmt->bind_param("s", $data['datum']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Fehler beim Speichern des Eintrags: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Speichern: ' . $e->getMessage()
    ]);
}
