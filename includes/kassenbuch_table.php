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