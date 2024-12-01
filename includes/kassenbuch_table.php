<?php
// Paging-Parameter
$entries_per_page = 25;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Hole den aktuellen Kassenstart
$kassenstart_sql = "SELECT einnahme as betrag FROM kassenbuch_eintraege WHERE bemerkung = 'Kassenstart' ORDER BY datum DESC, id DESC LIMIT 1";
$kassenstart_result = $conn->query($kassenstart_sql);
$kassenstart = $kassenstart_result->fetch_assoc();
$startbetrag = $kassenstart['betrag'] ?? 0;

// Hole alle regulären Einträge chronologisch sortiert (älteste zuerst)
$sql = "SELECT * FROM kassenbuch_eintraege WHERE bemerkung != 'Kassenstart' ORDER BY datum ASC, id ASC";
$result = $conn->query($sql);
$all_entries = [];

// Berechne die Kassenstände chronologisch
$laufender_kassenstand = $startbetrag;
while ($row = $result->fetch_assoc()) {
    $row['kassenstand'] = $laufender_kassenstand + $row['einnahme'] - $row['ausgabe'];
    $laufender_kassenstand = $row['kassenstand'];
    array_unshift($all_entries, $row); // Füge am Anfang ein für umgekehrte Reihenfolge
}

// Hole nur die Einträge für die aktuelle Seite
$entries = array_slice($all_entries, $offset, $entries_per_page);

// Gesamtanzahl der Einträge
$total_entries = count($all_entries);
$total_pages = ceil($total_entries / $entries_per_page);
?>

<div class="table-responsive">
    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
    <div class="mb-3">
        <button type="button" id="massDeleteBtn" class="btn btn-danger" style="display: none;">
            <i class="bi bi-trash"></i> Ausgewählte Einträge löschen
        </button>
    </div>
    <?php endif; ?>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
                <th>
                    <input type="checkbox" id="selectAll" class="form-check-input">
                </th>
                <?php endif; ?>
                <th>Datum</th>
                <th>Bemerkung</th>
                <th class="text-end">Einnahme</th>
                <th class="text-end">Ausgabe</th>
                <th class="text-end">Kassenstand</th>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                <th>Aktionen</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
                    <td>
                        <input type="checkbox" class="form-check-input entry-checkbox" value="<?= $entry['id'] ?>">
                    </td>
                    <?php endif; ?>
                    <td><?= date('d.m.Y', strtotime($entry['datum'])) ?></td>
                    <td><?= htmlspecialchars($entry['bemerkung']) ?></td>
                    <td class="text-end"><?= number_format($entry['einnahme'], 2, ',', '.') ?> €</td>
                    <td class="text-end"><?= number_format($entry['ausgabe'], 2, ',', '.') ?> €</td>
                    <td class="text-end"><?= number_format($entry['kassenstand'], 2, ',', '.') ?> €</td>
                    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" onclick="editEntry(<?= $entry['id'] ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin'])): ?>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteEntry(<?= $entry['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <nav aria-label="Kassenbuch Navigation">
        <ul class="pagination justify-content-center">
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
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
                echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                echo '</li>';
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
    <?php endif; ?>
</div> 