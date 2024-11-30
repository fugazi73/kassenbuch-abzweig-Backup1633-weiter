<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/column_config.php';

// Prüfe Admin-Berechtigung
if (!is_admin()) {
    header('Location: /');
    exit;
}

// Verarbeite POST-Anfrage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapping = [];
    foreach ($_POST['mapping'] as $db_field => $excel_column) {
        if (!empty($excel_column)) {
            $mapping[$db_field] = $excel_column;
        }
    }
    
    if (saveColumnMapping($mapping)) {
        $success_message = "Spaltenzuordnung wurde gespeichert";
    } else {
        $error_message = "Fehler beim Speichern der Spaltenzuordnung";
    }
}

// Hole aktuelle Spaltenzuordnung
$current_mapping = loadColumnMapping() ?? [];

// Hole Excel-Spalten wenn eine Datei hochgeladen wurde
$excel_columns = [];
if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
    $excel_columns = getExcelColumns($_FILES['excel_file']['tmp_name']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Excel-Spaltenzuordnung</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .mapping-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .mapping-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .mapping-label {
            flex: 0 0 150px;
            font-weight: bold;
        }
        .mapping-select {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel-Spaltenzuordnung</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (empty($excel_columns)): ?>
            <div class="mapping-form">
                <h2>Excel-Datei hochladen</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mapping-row">
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Hochladen</button>
                </form>
            </div>
        <?php else: ?>
            <div class="mapping-form">
                <h2>Spalten zuordnen</h2>
                <form action="" method="post">
                    <?php foreach ($default_columns as $db_field => $label): ?>
                        <div class="mapping-row">
                            <label class="mapping-label"><?php echo htmlspecialchars($label); ?></label>
                            <select name="mapping[<?php echo $db_field; ?>]" class="mapping-select" required>
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($excel_columns as $col => $name): ?>
                                    <option value="<?php echo $col; ?>" 
                                            <?php echo isset($current_mapping[$db_field]) && $current_mapping[$db_field] === $col ? 'selected' : ''; ?>>
                                        <?php echo $col . ' - ' . htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 