<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Prüfe Löschberechtigung
    if (!check_permission('delete_entries')) {
        throw new Exception('Keine Berechtigung zum Löschen von Einträgen');
    }

    // Validiere Request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfragemethode');
    }

    if (!isset($_POST['id'])) {
        throw new Exception('Keine ID angegeben');
    }

    $id = intval($_POST['id']);

    // Prüfe ob der Eintrag existiert
    $check_stmt = $conn->prepare("SELECT bemerkung FROM kassenbuch_eintraege WHERE id = ?");
    $check_stmt->bind_param('i', $id);
    
    if (!$check_stmt->execute()) {
        throw new Exception('Datenbankfehler beim Prüfen des Eintrags');
    }
    
    $result = $check_stmt->get_result();
    $entry = $result->fetch_assoc();
    
    if (!$entry) {
        throw new Exception('Eintrag nicht gefunden');
    }

    // Verhindere das Löschen des Kassenstart-Eintrags
    if ($entry['bemerkung'] === 'Kassenstart') {
        throw new Exception('Der Kassenstart-Eintrag kann nicht gelöscht werden');
    }

    // Lösche den Eintrag
    $delete_stmt = $conn->prepare("DELETE FROM kassenbuch_eintraege WHERE id = ?");
    $delete_stmt->bind_param('i', $id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Fehler beim Löschen des Eintrags: ' . $delete_stmt->error);
    }

    if ($delete_stmt->affected_rows === 0) {
        throw new Exception('Eintrag konnte nicht gelöscht werden');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Eintrag erfolgreich gelöscht'
    ]);

} catch (Exception $e) {
    http_response_code(200); // Sende 200 statt 500 für bessere Client-Verarbeitung
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}