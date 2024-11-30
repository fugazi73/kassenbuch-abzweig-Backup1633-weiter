<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/init.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

try {
    $conn->begin_transaction();

    // Speichere Standard-Spalten-Mappings
    if (isset($_POST['columns']['standard'])) {
        foreach ($_POST['columns']['standard'] as $key => $config) {
            $mapping_key = 'excel_mapping_' . $key;
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $mapping_key, $config['excel_column'], $config['excel_column']);
            $stmt->execute();
        }
    }

    // Speichere benutzerdefinierte Spalten
    if (isset($_POST['columns']['custom'])) {
        $custom_columns = array_values($_POST['columns']['custom']); // Array neu indizieren
        
        // Speichere in settings
        $columns_json = json_encode($custom_columns);
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES ('custom_columns', ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param('ss', $columns_json, $columns_json);
        $stmt->execute();

        // Erstelle neue Spalten in der Datenbank
        foreach ($custom_columns as $column) {
            $column_name = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($column['name']));
            
            // Prüfe ob Spalte bereits existiert
            $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
            if ($result->num_rows === 0) {
                $sql_type = match($column['type']) {
                    'text' => 'VARCHAR(255)',
                    'date' => 'DATE',
                    'decimal' => 'DECIMAL(10,2)',
                    'integer' => 'INT',
                    default => 'VARCHAR(255)'
                };
                
                $sql = "ALTER TABLE kassenbuch_eintraege ADD COLUMN `$column_name` $sql_type";
                $conn->query($sql);
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit; 