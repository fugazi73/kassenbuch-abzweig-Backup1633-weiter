<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

// HTML Header
echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Datenbank Info</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>";

// Tabellen auflisten
$tables = $conn->query("SHOW TABLES");

while ($table = $tables->fetch_array()) {
    $tableName = $table[0];
    echo "<div class='card mb-4'>
          <div class='card-header'>
            <h3>Tabelle: {$tableName}</h3>
          </div>
          <div class='card-body'>";
    
    // Tabellenstruktur
    echo "<h4>Struktur:</h4>";
    $structure = $conn->query("DESCRIBE {$tableName}");
    echo "<table class='table table-striped table-bordered mb-4'>
          <thead>
            <tr>
                <th>Feld</th>
                <th>Typ</th>
                <th>Null</th>
                <th>Schlüssel</th>
                <th>Standard</th>
                <th>Extra</th>
            </tr>
          </thead>
          <tbody>";
    
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

    // Tabelleninhalt
    echo "<h4>Inhalt:</h4>";
    $content = $conn->query("SELECT * FROM {$tableName} LIMIT 100");
    
    if ($content->num_rows > 0) {
        echo "<div class='table-responsive'><table class='table table-striped table-bordered'>
              <thead><tr>";
        
        $fields = $content->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr></thead><tbody>";

        while ($row = $content->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p class='text-muted'>Keine Daten vorhanden</p>";
    }

    echo "</div></div>";
}

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 