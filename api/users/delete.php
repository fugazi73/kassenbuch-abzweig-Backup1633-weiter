<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Prüfe Berechtigung
    if (!check_permission('manage_users')) {
        throw new Exception('Keine Berechtigung');
    }

    // Validiere Eingaben
    if (empty($_POST['id'])) {
        throw new Exception('Benutzer-ID fehlt');
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Ungültige Benutzer-ID');
    }

    // Verhindere Löschen des eigenen Accounts
    if ($id === $_SESSION['user_id']) {
        throw new Exception('Sie können Ihren eigenen Account nicht löschen');
    }

    // Lösche Benutzer
    $stmt = $conn->prepare("DELETE FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Benutzer wurde erfolgreich gelöscht'
        ]);
    } else {
        throw new Exception('Fehler beim Löschen des Benutzers');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 