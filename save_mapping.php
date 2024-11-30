<?php
require_once 'config.php';
require_once 'includes/init.php';

header('Content-Type: application/json');

try {
    if (!is_admin()) {
        throw new Exception('Keine Berechtigung');
    }

    // Starte Transaktion
    $conn->begin_transaction();

    // Verarbeite Standard-Mapping
    if (isset($_POST['standard_mapping']) && is_array($_POST['standard_mapping'])) {
        // Lösche nur die Standard-Mappings
        $standard_columns = ['datum', 'beleg_nr', 'bemerkung', 'einnahme', 'ausgabe'];
        $placeholders = str_repeat('?,', count($standard_columns) - 1) . '?';
        $sql = "DELETE FROM settings WHERE setting_key IN ('excel_mapping_" . implode("','excel_mapping_", $standard_columns) . "')";
        $conn->query($sql);

        // Speichere neue Standard-Mappings
        foreach ($_POST['standard_mapping'] as $column => $excel_column) {
            if (empty($excel_column)) continue;

            $setting_key = 'excel_mapping_' . $column;
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param('ss', $setting_key, $excel_column);
            $stmt->execute();
        }
    }

    // Verarbeite benutzerdefinierte Mappings
    if (isset($_POST['mapping']) && is_array($_POST['mapping'])) {
        foreach ($_POST['mapping'] as $column => $excel_column) {
            if (empty($excel_column)) continue;

            $setting_key = 'excel_mapping_' . $column;
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $setting_key, $excel_column, $excel_column);
            $stmt->execute();
        }
    }

    // Commit Transaktion
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Spaltenzuordnung erfolgreich gespeichert'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 