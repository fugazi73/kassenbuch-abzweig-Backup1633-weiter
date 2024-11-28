<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn->begin_transaction();
    
    // Validierung und Bereinigung der Eingaben
    $datum = filter_var($_POST['datum'], FILTER_SANITIZE_STRING);
    $bemerkung = filter_var($_POST['bemerkung'], FILTER_SANITIZE_STRING);
    $einnahme = !empty($_POST['einnahme']) ? floatval(str_replace(',', '.', $_POST['einnahme'])) : 0;
    $ausgabe = !empty($_POST['ausgabe']) ? floatval(str_replace(',', '.', $_POST['ausgabe'])) : 0;
    
    // Beleg-Nummer generieren
    $beleg_nr = generateBelegNr($datum, $conn);
    
    // Aktuellen Kassenstand ermitteln
    $result = $conn->query("SELECT kassenstand FROM kassenbuch_eintraege ORDER BY datum DESC, id DESC LIMIT 1");
    $current = $result->fetch_assoc();
    $kassenstand = $current ? $current['kassenstand'] : 0;
    
    // Neuen Kassenstand berechnen
    $neuer_kassenstand = $kassenstand + $einnahme - $ausgabe;
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege (datum, beleg_nr, bemerkung, einnahme, ausgabe, kassenstand, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdddi", $datum, $beleg_nr, $bemerkung, $einnahme, $ausgabe, $neuer_kassenstand, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'beleg_nr' => $beleg_nr
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
