<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}
check_login();
$page_title = 'Übersicht | Kassenbuch';
require_once 'includes/header.php';
// Pagination-Variablen hinzufügen
$items_per_page = 25;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Bestehende Saldo-Abfrage beibehalten
$result = $conn->query("SELECT 
    SUM(einnahme) - SUM(ausgabe) as gesamt_saldo,
    (SELECT kassenstand FROM kassenbuch_eintraege ORDER BY id DESC LIMIT 1) as aktueller_kassenstand
FROM kassenbuch_eintraege");

$current = $result->fetch_assoc();
$current_saldo = $current['gesamt_saldo'] ?? 0.00;
$current_kassenstand = $current['aktueller_kassenstand'] ?? 0.00;

// Bestehende Query-Logik beibehalten und um Pagination erweitern
$query = "SELECT * FROM kassenbuch_eintraege WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['von_datum']) && !empty($_GET['bis_datum'])) {
    $query .= " AND datum BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $_GET['von_datum'];
    $params[] = $_GET['bis_datum'];
}

if (!empty($_GET['typ'])) {
    if ($_GET['typ'] === 'einnahme') {
        $query .= " AND einnahme > 0";
    } elseif ($_GET['typ'] === 'ausgabe') {
        $query .= " AND ausgabe > 0";
    }
}

if (!empty($_GET['bemerkung']) && is_array($_GET['bemerkung'])) {
    $bemerkungen = array_map(function($b) use ($conn) {
        return $conn->real_escape_string($b);
    }, $_GET['bemerkung']);
    
    $query .= " AND bemerkung IN ('" . implode("','", $bemerkungen) . "')";
}

// Gesamtanzahl der Einträge ermitteln
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Bestehende Bemerkungen aus der Datenbank holen
$stmt_bemerkungen = $conn->prepare("SELECT DISTINCT bemerkung FROM kassenbuch_eintraege WHERE bemerkung != '' ORDER BY bemerkung");
$stmt_bemerkungen->execute();
$result_bemerkungen = $stmt_bemerkungen->get_result();
$bemerkungen = [];
while ($row = $result_bemerkungen->fetch_assoc()) {
    $bemerkungen[] = $row['bemerkung'];
}

// Hauptquery vorbereiten
$query = "SELECT * FROM kassenbuch_eintraege WHERE 1=1";
$params = [];
$types = "";

// Filter für Datum
if (!empty($_GET['von_datum']) && !empty($_GET['bis_datum'])) {
    $query .= " AND datum BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $_GET['von_datum'];
    $params[] = $_GET['bis_datum'];
}

// Filter für Typ
if (!empty($_GET['typ'])) {
    if ($_GET['typ'] === 'einnahme') {
        $query .= " AND einnahme > 0";
    } elseif ($_GET['typ'] === 'ausgabe') {
        $query .= " AND ausgabe > 0";
    }
}

// **Korrigierter Bemerkung-Filter**
if (!empty($_GET['bemerkung'])) {
    $bemerkung_filter = $_GET['bemerkung'];
    $query .= " AND bemerkung = ?";
    $types .= "s";
    $params[] = $bemerkung_filter;
}

// Gesamtanzahl der Einträge ermitteln
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Hauptquery mit Pagination
$query .= " ORDER BY datum DESC, id DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $items_per_page;
$params[] = $offset;

// Query ausführen
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container mt-4">
    <!-- Header-Bereich -->
    <div class="row align-items-center mb-4">
        <div class="col-auto">
            <h2 class="mb-0">
                <i class="bi bi-journal-text"></i> Kassenbuch
            </h2>
        </div>
        <div class="col text-end">
            <div class="kassenstand-display">
                <span class="kassenstand-label">Kassenstand:</span>
                <span class="kassenstand-value <?= $current_kassenstand >= 0 ? 'positive' : 'negative' ?>">
                    <?= number_format($current_kassenstand, 2, ',', '.') ?> €
                </span>
            </div>
        </div>
    </div>

    <!-- Datalist für Bemerkungen -->
    <datalist id="bemerkungen">
        <?php foreach ($bemerkungen as $bemerkung): ?>
            <option value="<?= htmlspecialchars($bemerkung) ?>">
        <?php endforeach; ?>
    </datalist>

    <!-- Eingabezeile über der Tabelle -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <input type="date" id="datum" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col">
                    <input type="text" id="bemerkung" class="form-control" placeholder="Bemerkung" required>
                </div>
                <div class="col">
                    <input type="number" id="einnahme" class="form-control" placeholder="Einnahme" step="0.01" min="0">
                </div>
                <div class="col">
                    <input type="number" id="ausgabe" class="form-control" placeholder="Ausgabe" step="0.01" min="0">
                </div>
                <div class="col-auto">
                    <button onclick="saveEntry()" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Neuer Eintrag
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabellen-Bereich -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Beleg-Nr.</th>
                        <th>Bemerkung</th>
                        <th class="text-end">Einnahme</th>
                        <th class="text-end">Ausgabe</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Kassenstand</th>
                        <th class="text-center">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d.m.Y', strtotime($row['datum'])) ?></td>
                        <td><?= htmlspecialchars($row['beleg_nr']) ?></td>
                        <td><?= htmlspecialchars($row['bemerkung']) ?></td>
                        <td class="text-end">
                            <?php if ($row['einnahme'] > 0): ?>
                                <span class="betrag betrag-positiv">
                                    <?= number_format($row['einnahme'], 2, ',', '.') ?> €
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($row['ausgabe'] > 0): ?>
                                <span class="betrag betrag-negativ">
                                    <?= number_format($row['ausgabe'], 2, ',', '.') ?> €
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="betrag <?= $row['saldo'] >= 0 ? 'betrag-positiv' : 'betrag-negativ' ?>">
                                <?= number_format($row['saldo'], 2, ',', '.') ?> €
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="betrag kassenstand">
                                <?= number_format($row['kassenstand'], 2, ',', '.') ?> €
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-id="<?= $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $row['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted">
            Zeige <?= min($total_records, ($offset + 1)) ?>-<?= min($total_records, ($offset + $items_per_page)) ?> 
            von <?= $total_records ?> Einträgen
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Seitennavigation">
            <ul class="pagination mb-0">
                <!-- Erste Seite -->
                <li class="page-item <?= ($current_page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= get_pagination_url(1) ?>" aria-label="Erste">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
                
                <!-- Vorherige Seite -->
                <li class="page-item <?= ($current_page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= get_pagination_url(max(1, $current_page - 1)) ?>" aria-label="Vorherige">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                
                <!-- Seitenzahlen -->
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                    <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= get_pagination_url($i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <!-- Nächste Seite -->
                <li class="page-item <?= ($current_page == $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= get_pagination_url(min($total_pages, $current_page + 1)) ?>" aria-label="Nächste">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                
                <!-- Letzte Seite -->
                <li class="page-item <?= ($current_page == $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= get_pagination_url($total_pages) ?>" aria-label="Letzte">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Eintrag Modal -->
    <div class="modal fade" id="entryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Neuer Eintrag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="entryForm">
                        <input type="hidden" id="entry_id" name="entry_id">
                        <div class="mb-3">
                            <label for="date" class="form-label">Datum</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <input type="text" class="form-control" id="description" name="description" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Betrag</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Typ</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="Einnahme">Einnahme</option>
                                <option value="Ausgabe">Ausgabe</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="submit" class="btn btn-primary">Speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal für die Bearbeitung -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eintrag bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" onsubmit="updateEntry(event)">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Datum</label>
                            <input type="date" name="datum" id="edit_datum" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Beleg</label>
                            <input type="text" name="beleg" id="edit_beleg" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bemerkung</label>
                            <input type="text" name="bemerkung" id="edit_bemerkung" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Einnahme</label>
                                <input type="number" name="einnahme" id="edit_einnahme" 
                                       class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ausgabe</label>
                                <input type="number" name="ausgabe" id="edit_ausgabe" 
                                       class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript für das Modal und die Einträge -->
    <script src="js/kassenbuch/entry-manager.js"></script>

    <!-- Bootstrap Bundle hinzufügen -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/kassenbuch/stats-manager.js"></script>

    <script src="js/kassenbuch/filter-manager.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.entryManager = new EntryManager();
        window.statsManager = new StatsManager();
        window.filterManager = new FilterManager();
    });
    </script>

    <!-- Fügen Sie die Select2 CSS und JS ein -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Fügen Sie die Select2 Bootstrap Theme CSS hinzu -->
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet">

    <!-- Füge im Header den Lato Font hinzu -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">

    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Administrator', 'admin', 'Admin', 'Chef'])): ?>
        <!-- Filter-Bereich -->
        <div class="filter-container mb-4">
            <!-- ... Filter-Inhalt ... -->
        </div>
    <?php endif; ?>

    <!-- Script einbinden -->
    <script src="js/kassenbuch/kassenbuch.js"></script>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>

