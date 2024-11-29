<?php
session_start();
require_once 'config.php';
check_login();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->begin_transaction();
<<<<<<< HEAD

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
=======
    
    // Validierung und Bereinigung der Eingaben
    $datum = filter_var($_POST['datum'], FILTER_SANITIZE_STRING);
    $bemerkung = filter_var($_POST['bemerkung'], FILTER_SANITIZE_STRING);
    $einnahme = floatval($_POST['einnahme'] ?? 0);
    $ausgabe = floatval($_POST['ausgabe'] ?? 0);
    $saldo = $einnahme - $ausgabe;
    
    // Beleg-Nummer generieren
    $beleg_nr = generateBelegNr($datum, $conn);
    
    // Kassenstand vom vorherigen Eintrag holen
    $result = $conn->query("SELECT kassenstand FROM kassenbuch_eintraege 
                           ORDER BY datum DESC, id DESC LIMIT 1");
    $current = $result->fetch_assoc();
    $vorheriger_kassenstand = $current ? $current['kassenstand'] : 0;
    
    // Neuer Kassenstand berechnen
    $neuer_kassenstand = $vorheriger_kassenstand + $saldo;
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege 
                           (datum, beleg_nr, bemerkung, einnahme, ausgabe, kassenstand, user_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdddd", $datum, $beleg_nr, $bemerkung, $einnahme, $ausgabe, $neuer_kassenstand, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)

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
