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
    if (empty($_GET['id'])) {
        throw new Exception('Benutzer-ID fehlt');
    }

    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Ungültige Benutzer-ID');
    }

    // Hole Benutzerdaten
    $stmt = $conn->prepare("SELECT id, username, role, active FROM benutzer WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        throw new Exception('Benutzer nicht gefunden');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 