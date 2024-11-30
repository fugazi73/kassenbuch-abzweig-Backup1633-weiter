<?php
// Paging-Parameter
$entries_per_page = 25;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Hole den Kassenstart
$kassenstart_sql = "SELECT einnahme as betrag 
                    FROM kassenbuch_eintraege 
                    WHERE bemerkung = 'Kassenstart' 
                    ORDER BY datum DESC, id DESC 
                    LIMIT 1";
$kassenstart_result = $conn->query($kassenstart_sql);
$startbetrag = $kassenstart_result->fetch_assoc()['betrag'] ?? 0;

// Hole alle regulären Einträge chronologisch sortiert (neueste zuerst)
$sql = "SELECT * FROM kassenbuch_eintraege 
        WHERE bemerkung != 'Kassenstart' 
        AND bemerkung != 'Startbetrag'
        ORDER BY datum DESC, id DESC";
$result = $conn->query($sql);
$all_entries = [];

// Berechne die Summe aller Salden
$saldo_summe = 0;
while ($row = $result->fetch_assoc()) {
    $saldo = $row['einnahme'] - $row['ausgabe'];
    $saldo_summe += $saldo;
    $all_entries[] = $row;
}

// Berechne die Kassenstände von oben nach unten
$laufender_kassenstand = $startbetrag + $saldo_summe;
foreach ($all_entries as &$row) {
    $row['kassenstand'] = $laufender_kassenstand;
    $saldo = $row['einnahme'] - $row['ausgabe'];
    $laufender_kassenstand -= $saldo;
}

// Hole nur die Einträge für die aktuelle Seite
$entries = array_slice($all_entries, $offset, $entries_per_page);

// Gesamtanzahl der Einträge
$total_entries = count($all_entries);
$total_pages = ceil($total_entries / $entries_per_page);

// Tabellen-Header
?>
<div class="table-responsive">
    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
    <div class="mb-3">
        <button type="button" id="massDeleteBtn" class="btn btn-danger" style="display: none;">
            <i class="bi bi-trash"></i> Ausgewählte Einträge löschen
        </button>
    </div>
    <?php endif; ?>

    <!-- Paging-Navigation oben -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="paging-info">
            Seite <?= $current_page ?> von <?= $total_pages ?>
            (<?= $total_entries ?> Einträge)
        </div>
        <nav aria-label="Kassenbuch Navigation">
            <ul class="pagination mb-0">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1" title="Erste Seite">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>" title="Vorherige Seite">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Seitenzahlen anzeigen
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = $i === $current_page ? ' active' : '';
                    echo '<li class="page-item' . $active . '">
                            <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                          </li>';
                }

                if ($end_page < $total_pages) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>" title="Nächste Seite">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?>" title="Letzte Seite">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

    <table class="table table-hover">
        <thead>
            <tr>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
                <th>
                    <input type="checkbox" id="selectAll" class="form-check-input">
                </th>
                <?php endif; ?>
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
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
                <td>
                    <input type="checkbox" class="form-check-input entry-checkbox" data-entry-id="<?= $entry['id'] ?>">
                </td>
                <?php endif; ?>
                <td><?= date('d.m.Y', strtotime($entry['datum'])) ?></td>
                <td><?= htmlspecialchars($entry['beleg_nr']) ?></td>
                <td><?= htmlspecialchars($entry['bemerkung']) ?></td>
                <td class="text-end"><?= number_format($entry['einnahme'], 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($entry['ausgabe'], 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($saldo, 2, ',', '.') ?> €</td>
                <td class="text-end"><?= number_format($entry['kassenstand'], 2, ',', '.') ?> €</td>
                <?php
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

    <!-- Paging-Navigation unten -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="paging-info">
            Seite <?= $current_page ?> von <?= $total_pages ?>
            (<?= $total_entries ?> Einträge)
        </div>
        <nav aria-label="Kassenbuch Navigation">
            <ul class="pagination mb-0">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1" title="Erste Seite">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>" title="Vorherige Seite">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                // Seitenzahlen anzeigen
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = $i === $current_page ? ' active' : '';
                    echo '<li class="page-item' . $active . '">
                            <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                          </li>';
                }

                if ($end_page < $total_pages) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>" title="Nächste Seite">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?>" title="Letzte Seite">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div> 