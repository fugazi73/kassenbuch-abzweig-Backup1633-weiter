<?php
require_once '../../includes/init.php';
require_once '../../config.php';

// Nur für Admins zugänglich
if (!is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Zugriff verweigert'
    ]);
    exit;
}

try {
    // Datenbankverbindung herstellen
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Benutzer abrufen
    $stmt = $pdo->prepare("
        SELECT 
            id,
            username,
            email,
            role,
            active,
            last_login,
            created_at,
            updated_at
        FROM users
        ORDER BY username ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Erfolgreiche Antwort
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    // Fehlermeldung
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} 