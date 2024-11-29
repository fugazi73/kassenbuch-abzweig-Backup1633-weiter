<?php
// Hole den Kassenstart aus den Settings
$kassenstart_query = $conn->query("
    SELECT setting_value as betrag 
    FROM settings 
    WHERE setting_key = 'cash_start'
    LIMIT 1
");
$kassenstart = $kassenstart_query->fetch_assoc()['betrag'] ?? 0;

// Erst alle Einträge chronologisch sortiert holen und Kassenstand berechnen
$sql = "SELECT * FROM kassenbuch_eintraege 
        WHERE bemerkung != 'Kassenstart' 
        ORDER BY datum ASC, id ASC";
$temp_result = $conn->query($sql);
$entries = [];
$laufender_kassenstand = floatval($kassenstart);

while ($row = $temp_result->fetch_assoc()) {
    $saldo = $row['einnahme'] - $row['ausgabe'];
    $laufender_kassenstand += $saldo;
    $row['kassenstand'] = $laufender_kassenstand;
    $entries[] = $row;
}

// Dann die Einträge umdrehen für die Anzeige
$entries = array_reverse($entries);

// Tabellen-Header
?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Datum</th>
                <th>Beleg-Nr.</th>
                <th>Bemerkung</th>
                <th class="text-end">Einnahme</th>
                <th class="text-end">Ausgabe</th>
                <th class="text-end">Saldo</th>
                <th class="text-end">Kassenstand</th>
                <?php
                // Hole benutzerdefinierte Spalten
                $custom_columns = json_decode($settings['custom_columns'] ?? '[]', true);
                foreach ($custom_columns as $column) {
                    echo '<th class="text-end">' . htmlspecialchars($column['name']) . '</th>';
                }
                ?>
                <th class="text-end">Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): 
            $saldo = $entry['einnahme'] - $entry['ausgabe'];
        ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($entry['datum'])) ?></td>
                <td><?= htmlspecialchars($entry['beleg_nr']) ?></td>
                <td><?= htmlspecialchars($entry['bemerkung']) ?></td>
                <td class="text-end"><?= number_format($entry['einnahme'], 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($entry['ausgabe'], 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($saldo, 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($entry['kassenstand'], 2, ',', '.') ?> €</td>
                <?php
                // Zeige benutzerdefinierte Spalten
                foreach ($custom_columns as $column) {
                    $column_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $column['name']));
                    $value = $entry[$column_name] ?? '';
                    
                    echo '<td class="text-end">';
                    switch($column['type']) {
                        case 'date':
                            echo $value ? date('d.m.Y', strtotime($value)) : '';
                            break;
                        case 'decimal':
                            echo $value ? number_format($value, 2, ',', '.') . ' €' : '';
                            break;
                        case 'integer':
                            echo $value ? number_format($value, 0, ',', '.') : '';
                            break;
                        default:
                            echo htmlspecialchars($value);
                    }
                    echo '</td>';
                }
                ?>
                <td class="text-end">
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editEntry(<?= $entry['id'] ?>)" title="Bearbeiten">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteEntry(<?= $entry['id'] ?>)" title="Löschen">
                            <i class="bi bi-trash"></i>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div> 