<?php
// Fehlerbehandlung
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Prüfe Authentifizierung
if (!check_login() || !is_admin()) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'message' => 'Keine Berechtigung'
    ]));
}

try {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'stats':
            $stats = [
                'system' => getSystemStats(),
                'users' => getUserStats($conn),
                'activity' => getActivityStats($conn),
                'backup' => getBackupStats($conn)
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            throw new Exception('Ungültige Aktion');
    }
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Laden der Daten'
    ]);
}

function getUserStats($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users) as total,
                (SELECT COUNT(DISTINCT user_id) 
                 FROM kassenbuch_eintraege 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ) as active
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? ['total' => 0, 'active' => 0];
    } catch (Exception $e) {
        error_log("Error in getUserStats: " . $e->getMessage());
        return ['total' => 0, 'active' => 0];
    }
}

function getSystemStats() {
    try {
        return [
            'status' => 'normal',
            'message' => 'System läuft normal',
            'memory' => memory_get_usage(true),
            'disk' => disk_free_space("/") ?? 0
        ];
    } catch (Exception $e) {
        error_log("Error in getSystemStats: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Systemstatus nicht verfügbar',
            'memory' => 0,
            'disk' => 0
        ];
    }
}

function getActivityStats($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT ke.*, u.username
            FROM kassenbuch_eintraege ke
            JOIN users u ON ke.user_id = u.id
            ORDER BY ke.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Error in getActivityStats: " . $e->getMessage());
        return [];
    }
}

function getBackupStats($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT created_at as lastBackup, size 
            FROM backups 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [
            'lastBackup' => null,
            'size' => 0
        ];
    } catch (Exception $e) {
        error_log("Error in getBackupStats: " . $e->getMessage());
        return [
            'lastBackup' => null,
            'size' => 0
        ];
    }
} 