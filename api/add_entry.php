<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Prüfe Berechtigung
    if (!check_permission('add_entries')) {
        throw new Exception('Keine Berechtigung zum Hinzufügen von Einträgen');
    }

    // Validiere Request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfragemethode');
    }

    // Hole und validiere Daten
    $datum = $_POST['datum'] ?? null;
    $beleg_nr = $_POST['beleg_nr'] ?? '';
    $bemerkung = $_POST['bemerkung'] ?? '';
    $einnahme = floatval(str_replace(',', '.', $_POST['einnahme'] ?? 0));
    $ausgabe = floatval(str_replace(',', '.', $_POST['ausgabe'] ?? 0));

    // Validierung
    if (empty($datum)) {
        throw new Exception('Datum ist erforderlich');
    }
    if (empty($bemerkung)) {
        throw new Exception('Bemerkung ist erforderlich');
    }

    // Verhindere gleichzeitige Einnahmen und Ausgaben
    if ($einnahme > 0 && $ausgabe > 0) {
        throw new Exception('Ein Eintrag kann nicht gleichzeitig Einnahme und Ausgabe sein');
    }

    // Mindestens ein Wert muss größer als 0 sein
    if ($einnahme <= 0 && $ausgabe <= 0) {
        throw new Exception('Bitte geben Sie entweder eine Einnahme oder eine Ausgabe ein');
    }

    // Füge den Eintrag hinzu
    $stmt = $conn->prepare("
        INSERT INTO kassenbuch_eintraege (datum, beleg_nr, bemerkung, einnahme, ausgabe)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param('sssdd',
        $datum,
        $beleg_nr,
        $bemerkung,
        $einnahme,
        $ausgabe
    );

    if (!$stmt->execute()) {
        throw new Exception('Fehler beim Hinzufügen des Eintrags: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Eintrag erfolgreich hinzugefügt',
        'entry_id' => $stmt->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 