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

// Basis-SQL für reguläre Einträge
$sql = "SELECT * FROM kassenbuch_eintraege WHERE bemerkung != 'Kassenstart'";
$params = [];
$types = "";

// Monats-Filter
if (isset($_GET['monat']) && !empty($_GET['monat'])) {
    $monat_jahr = explode('.', $_GET['monat']);
    if (count($monat_jahr) == 2) {
        $monat = $monat_jahr[0];
        $jahr = $monat_jahr[1];
        $sql .= " AND MONTH(datum) = ? AND YEAR(datum) = ?";
        $params[] = $monat;
        $params[] = $jahr;
        $types .= "ss";
    }
}

// Datum-Filter (nur wenn kein Monatsfilter aktiv)
if (!isset($_GET['monat']) || empty($_GET['monat'])) {
    if (isset($_GET['von_datum']) && !empty($_GET['von_datum'])) {
        $von_datum = date('Y-m-d', strtotime(str_replace('.', '-', $_GET['von_datum'])));
        $sql .= " AND datum >= ?";
        $params[] = $von_datum;
        $types .= "s";
    }

    if (isset($_GET['bis_datum']) && !empty($_GET['bis_datum'])) {
        $bis_datum = date('Y-m-d', strtotime(str_replace('.', '-', $_GET['bis_datum'])));
        $sql .= " AND datum <= ?";
        $params[] = $bis_datum;
        $types .= "s";
    }
}

// Typ-Filter
if (isset($_GET['typ']) && !empty($_GET['typ'])) {
    if ($_GET['typ'] === 'einnahme') {
        $sql .= " AND einnahme > 0";
    } elseif ($_GET['typ'] === 'ausgabe') {
        $sql .= " AND ausgabe > 0";
    }
}

// Bemerkungen-Filter
if (isset($_GET['bemerkung']) && !empty($_GET['bemerkung'])) {
    $sql .= " AND bemerkung = ?";
    $params[] = $_GET['bemerkung'];
    $types .= "s";
}

// Sortierung
$sql .= " ORDER BY datum DESC, id DESC";

// Prepared Statement vorbereiten und ausführen
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Alle gefilterten Einträge laden
$filtered_entries = [];
while ($row = $result->fetch_assoc()) {
    $filtered_entries[] = $row;
}

// Kassenstand für gefilterte Einträge berechnen
$laufender_kassenstand = $startbetrag;
$all_entries = [];

// Kassenstand für jeden Eintrag berechnen (von alt nach neu)
for ($i = count($filtered_entries) - 1; $i >= 0; $i--) {
    $row = $filtered_entries[$i];
    $laufender_kassenstand += $row['einnahme'] - $row['ausgabe'];
    $row['kassenstand'] = $laufender_kassenstand;
    array_unshift($all_entries, $row);
}

// Paging-Parameter
$entries_per_page = 25;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_entries = count($all_entries);
$total_pages = ceil($total_entries / $entries_per_page);

// Sicherstellen, dass die aktuelle Seite nicht größer ist als die Gesamtanzahl der Seiten
$current_page = min($current_page, $total_pages);
$current_page = max(1, $current_page); // Mindestens Seite 1

$offset = ($current_page - 1) * $entries_per_page;
$entries = array_slice($all_entries, $offset, $entries_per_page);

// Pagination Links mit Filtern erstellen
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<div class="table-responsive">
    <?php if (check_permission('delete_entries')): ?>
    <div class="mb-3">
        <button type="button" id="massDeleteBtn" class="btn btn-danger" style="display: none;">
            <i class="bi bi-trash"></i> Ausgewählte Einträge löschen
        </button>
    </div>
    <?php endif; ?>

    <?php if (empty($entries)): ?>
    <div class="alert alert-info">
        Keine Einträge gefunden.
    </div>
    <?php else: ?>

    <?php
    // Aktive Filter anzeigen
    $active_filters = [];
    if (!empty($_GET['monat'])) {
        $monat_jahr = explode('.', $_GET['monat']);
        if (count($monat_jahr) == 2) {
            $timestamp = strtotime("01.{$_GET['monat']}");
            $active_filters[] = "Monat: " . strftime('%B %Y', $timestamp);
        }
    }
    if (!empty($_GET['von_datum'])) {
        $active_filters[] = "Von: " . $_GET['von_datum'];
    }
    if (!empty($_GET['bis_datum'])) {
        $active_filters[] = "Bis: " . $_GET['bis_datum'];
    }
    if (!empty($_GET['typ'])) {
        $active_filters[] = "Typ: " . ($_GET['typ'] === 'einnahme' ? 'Einnahmen' : 'Ausgaben');
    }
    if (!empty($_GET['bemerkung'])) {
        $active_filters[] = "Bemerkung: " . htmlspecialchars($_GET['bemerkung']);
    }

    if (!empty($active_filters)): ?>
    <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="bi bi-funnel-fill me-2"></i>
        <div class="flex-grow-1">
            Aktive Filter: <?= implode(' | ', $active_filters) ?>
        </div>
        <a href="kassenbuch.php" class="btn btn-outline-info btn-sm ms-3" title="Filter zurücksetzen">
            <i class="bi bi-x-circle"></i> Filter zurücksetzen
        </a>
    </div>
    <?php endif; ?>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <?php if (check_permission('delete_entries')): ?>
                <th>
                    <input type="checkbox" id="selectAll" class="form-check-input">
                </th>
                <?php endif; ?>
                <th>Datum</th>
                <th>Bemerkung</th>
                <th class="text-end">Einnahme</th>
                <th class="text-end">Ausgabe</th>
                <th class="text-end">Kassenstand</th>
                <?php if (check_permission('edit_entries') || check_permission('delete_entries')): ?>
                <th class="text-end">Aktionen</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <?php if (check_permission('delete_entries')): ?>
                    <td>
                        <input type="checkbox" class="form-check-input entry-checkbox" value="<?= $entry['id'] ?>">
                    </td>
                    <?php endif; ?>
                    <td><?= date('d.m.Y', strtotime($entry['datum'])) ?></td>
                    <td><?= htmlspecialchars($entry['bemerkung']) ?></td>
                    <td class="text-end <?= $entry['einnahme'] > 0 ? 'text-success fw-bold' : '' ?>">
                        <?= number_format($entry['einnahme'], 2, ',', '.') ?> €
                    </td>
                    <td class="text-end <?= $entry['ausgabe'] > 0 ? 'text-danger fw-bold' : '' ?>">
                        <?= number_format($entry['ausgabe'], 2, ',', '.') ?> €
                    </td>
                    <td class="text-end <?= $entry['kassenstand'] >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                        <?= number_format($entry['kassenstand'], 2, ',', '.') ?> €
                    </td>
                    <?php if (check_permission('edit_entries') || check_permission('delete_entries')): ?>
                    <td class="text-end">
                        <div class="btn-group">
                            <?php if (check_permission('edit_entries')): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="editEntry(<?= $entry['id'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if (check_permission('delete_entries')): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteEntry(<?= $entry['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
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
                    <a class="page-link" href="<?= buildPaginationUrl(1) ?>" title="Erste Seite">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPaginationUrl($current_page - 1) ?>" title="Vorherige Seite">
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
                echo '<a class="page-link" href="' . buildPaginationUrl($i) . '">' . $i . '</a>';
                echo '</li>';
            }

            if ($end_page < $total_pages) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPaginationUrl($current_page + 1) ?>" title="Nächste Seite">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPaginationUrl($total_pages) ?>" title="Letzte Seite">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div> 