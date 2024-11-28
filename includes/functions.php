/**
 * Einstellung in der Datenbank aktualisieren oder erstellen
 */
function updateSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (:key, :value) 
        ON DUPLICATE KEY UPDATE setting_value = :value
    ");
    
    return $stmt->execute([
        ':key' => $key,
        ':value' => $value
    ]);
} 