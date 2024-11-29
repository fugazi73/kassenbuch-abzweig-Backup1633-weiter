if (isset($_POST['save_column_config'])) {
    try {
        $conn->begin_transaction();

        // Speichere Basis-Spalten-Konfiguration
        foreach ($_POST['columns']['required'] as $key => $config) {
            $mapping_key = 'excel_mapping_' . $key;
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $mapping_key, $config['excel_column'], $config['excel_column']);
            $stmt->execute();
        }

        // Speichere zusätzliche Spalten
        if (isset($_POST['columns']['custom'])) {
            $custom_columns = json_encode($_POST['columns']['custom']);
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES ('custom_columns', ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $custom_columns, $custom_columns);
            $stmt->execute();

            // Erstelle oder aktualisiere Datenbanktabelle
            foreach ($_POST['columns']['custom'] as $column) {
                $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
                $column_type = match($column['type']) {
                    'text' => 'VARCHAR(255)',
                    'date' => 'DATE',
                    'decimal' => 'DECIMAL(10,2)',
                    'integer' => 'INT',
                    default => 'VARCHAR(255)'
                };

                // Prüfe ob Spalte existiert
                $result = $conn->query("SHOW COLUMNS FROM kassenbuch_eintraege LIKE '$column_name'");
                if ($result->num_rows === 0) {
                    $conn->query("ALTER TABLE kassenbuch_eintraege ADD COLUMN $column_name $column_type");
                }
            }
        }

        $conn->commit();
        $success_message = 'Spaltenkonfiguration wurde gespeichert.';
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = 'Fehler beim Speichern der Konfiguration: ' . $e->getMessage();
    }
} 