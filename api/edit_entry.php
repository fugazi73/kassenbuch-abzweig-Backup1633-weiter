<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Prüfe Bearbeitungsberechtigung
    if (!check_permission('edit_entries')) {
        throw new Exception('Keine Berechtigung zum Bearbeiten von Einträgen');
    }

    // GET-Anfrage: Lade Eintrag
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['id'])) {
            throw new Exception('Keine ID angegeben');
        }

        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM kassenbuch_eintraege WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Datenbankfehler beim Laden des Eintrags');
        }
        
        $result = $stmt->get_result();
        $entry = $result->fetch_assoc();
        
        if (!$entry) {
            throw new Exception('Eintrag nicht gefunden');
        }

        echo json_encode([
            'success' => true,
            'entry' => [
                'id' => $entry['id'],
                'datum' => $entry['datum'],
                'beleg_nr' => $entry['beleg_nr'],
                'bemerkung' => $entry['bemerkung'],
                'einnahme' => number_format($entry['einnahme'], 2, '.', ''),
                'ausgabe' => number_format($entry['ausgabe'], 2, '.', '')
            ]
        ]);
        exit;
    }

    // POST-Anfrage: Aktualisiere Eintrag
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['id'])) {
            throw new Exception('Keine ID angegeben');
        }

        $id = intval($_POST['id']);
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

        $stmt = $conn->prepare("
            UPDATE kassenbuch_eintraege 
            SET datum = ?, beleg_nr = ?, bemerkung = ?, einnahme = ?, ausgabe = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param('sssddi', 
            $datum, 
            $beleg_nr, 
            $bemerkung, 
            $einnahme, 
            $ausgabe, 
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Fehler beim Aktualisieren des Eintrags: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Eintrag wurde nicht gefunden oder keine Änderungen vorgenommen');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Eintrag erfolgreich aktualisiert'
        ]);
        exit;
    }

    throw new Exception('Ungültige Anfragemethode');

} catch (Exception $e) {
    http_response_code(200); // Sende 200 statt 500 für bessere Client-Verarbeitung
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}