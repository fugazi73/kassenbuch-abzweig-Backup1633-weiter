<?php
require_once 'config.php';
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

<!-- Header-Bereich -->
<div class="container-fluid" style="max-width: 1400px;">
    <!-- Header-Zeile mit Kassenbuch-Titel und Saldo -->
    <div class="row align-items-center mb-4">
        <div class="col-auto">
            <h2 class="mb-0">
                <i class="bi bi-journal-text"></i> Kassenbuch
            </h2>
        </div>
        <div class="col text-end">
            <span class="ms-3">
                <span class="text-muted">Aktueller Saldo:</span>
                <span id="current_saldo" class="fw-bold <?= $current_saldo >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($current_saldo, 2, ',', '.') ?> €
                </span>
            </span>
            <span class="ms-4">
                <span class="text-muted">Kassenstand:</span>
                <span id="current_kassenstand" class="fw-bold <?= $current_kassenstand >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= number_format($current_kassenstand, 2, ',', '.') ?> €
                </span>
            </span>
        </div>
    </div>

    <?php if (is_chef_or_admin()): ?>
    <!-- Statistik-Karten -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Einträge heute</h6>
                    <h4 id="stats_eintraege_heute" class="mb-0">17 Einträge</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Umsatz heute</h6>
                    <h4 id="stats_umsatz_heute" class="mb-0">31.250,00 €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Einträge diesen Monat</h6>
                    <h4 id="stats_eintraege_monat" class="mb-0">22 Einträge</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Umsatz diesen Monat</h6>
                    <h4 id="stats_umsatz_monat" class="mb-0">31.372,00 €</h4>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter-Bereich -->
    <?php if (is_chef_or_admin()): ?>
    <div class="card mb-4">
        <div class="card-body">
 <form id="filterForm" class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Von Datum</label>
        <input type="date" name="von_datum" class="form-control" value="<?= htmlspecialchars($_GET['von_datum'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Bis Datum</label>
        <input type="date" name="bis_datum" class="form-control" value="<?= htmlspecialchars($_GET['bis_datum'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Typ</label>
        <select name="typ" class="form-select">
            <option value="">Alle</option>
            <option value="einnahme" <?= ($_GET['typ'] ?? '') === 'einnahme' ? 'selected' : '' ?>>Einnahmen</option>
            <option value="ausgabe" <?= ($_GET['typ'] ?? '') === 'ausgabe' ? 'selected' : '' ?>>Ausgaben</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Bemerkung</label>
        <select name="bemerkung" class="form-select" data-live-search="true">
            <option value="">Alle Bemerkungen</option>
            <?php foreach ($bemerkungen as $bemerkung): ?>
                <option value="<?= htmlspecialchars($bemerkung) ?>"
                    <?= ($_GET['bemerkung'] ?? '') === $bemerkung ? 'selected' : '' ?>>
                    <?= htmlspecialchars($bemerkung) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Filtern</button>
        <button type="button" class="btn btn-secondary" onclick="resetFilter()">Zurücksetzen</button>
    </div>
</form>

        </div>
    </div>
    <?php endif; ?>

    <!-- Datalist für Bemerkungen -->
    <datalist id="bemerkungen">
        <?php foreach ($bemerkungen as $bemerkung): ?>
            <option value="<?= htmlspecialchars($bemerkung) ?>">
        <?php endforeach; ?>
    </datalist>

    <!-- Schnelles Eingabeformular -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <form id="quickEntryForm" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Datum</label>
                            <input type="date" name="datum" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bemerkung</label>
                            <input type="text" 
                                   name="bemerkung" 
                                   class="form-control" 
                                   required 
                                   list="bemerkungen" 
                                   autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Einnahme</label>
                            <input type="number" name="einnahme" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ausgabe</label>
                            <input type="number" name="ausgabe" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="saveEntry()">
                                <i class="bi bi-plus-circle"></i> Neuer Eintrag
                            </button>
                        </div>
                    </form>
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
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary btn-action" onclick="editEntry(<?= $row['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteEntry(<?= $row['id'] ?>)">
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
</div>

<!-- Angepasste Styles -->
<style>
.container-fluid {
    padding: 20px;
    margin: 0 auto;
}

.table-responsive {
    margin-bottom: 1rem;
}

.pagination {
    margin-bottom: 2rem;
}

.page-link {
    padding: 0.375rem 0.75rem;
}

.pagination .bi {
    font-size: 0.875rem;
}

/* Zusätzliche Abstände am unteren Rand */
.container-fluid > :last-child {
    margin-bottom: 40px;
}

/* Tabellen-Spaltenbreiten optimieren */
.table th:first-child,
.table td:first-child {
    width: 100px; /* Datum */
}

.table th:nth-child(2),
.table td:nth-child(2) {
    width: 120px; /* Bemerkung */
}

.table th:nth-child(4),
.table td:nth-child(4),
.table th:nth-child(5),
.table td:nth-child(5),
.table th:nth-child(6),
.table td:nth-child(6),
.table th:nth-child(7),
.table td:nth-child(7) {
    width: 130px; /* Beträge */
    text-align: right;
}

.table th:last-child,
.table td:last-child {
    width: 100px; /* Aktionen */
    text-align: right;
}

/* Container für die Tabelle */
.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem;
    margin-bottom: 2rem;
}

/* Tabelle */
.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1.1rem; /* Größere Schrift */
}

.table thead th {
    background-color: #f8f9fa;
    color: #333;
    font-weight: 700; /* Fettere Schrift */
    padding: 1.2rem 1rem;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
    font-size: 1.1rem; /* Größere Schrift */
}

.table tbody td {
    padding: 1.2rem 1rem;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: #f9f9f9;
}

/* Beträge formatieren */
.betrag {
    font-family: 'Roboto Mono', monospace;
    font-weight: 600; /* Fettere Schrift */
    font-size: 1.1rem; /* Größere Schrift */
    white-space: nowrap;
}

.betrag-positiv {
    color: #00a854; /* Helleres Grün */
}

.betrag-negativ {
    color: #dc3545; /* Rot */
}

.kassenstand {
    color: #0d6efd; /* Blau */
    font-weight: 700; /* Fettere Schrift */
}

/* Aktions-Buttons nebeneinander */
.action-buttons {
    white-space: nowrap;
    display: inline-flex;
    gap: 0.5rem; /* Abstand zwischen den Buttons */
}

.btn-action {
    padding: 0.5rem 0.7rem;
    border-radius: 4px;
    font-size: 1rem;
}

/* Pagination unten */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination .page-item .page-link {
    padding: 0.7rem 1rem;
    font-size: 1.1rem;
    font-weight: 500;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.text-end {
    text-align: right;
    white-space: nowrap;
}

.saldo-container {
    background-color: #f8f9fa; /* Heller Hintergrund */
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.saldo-text {
    font-size: 1.2rem; /* Größere Schrift */
    font-weight: bold;
    color: #333; /* Dunklere Schriftfarbe */
}
</style>

<!-- Modal für neue Einträge -->
<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neuer Eintrag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="entryForm">
                    <div class="mb-3">
                        <label class="form-label">Datum</label>
                        <input type="date" class="form-control" name="datum" required 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bemerkung</label>
                        <input type="text" class="form-control" name="bemerkung" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Einnahme</label>
                            <input type="number" class="form-control" name="einnahme" 
                                   step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ausgabe</label>
                            <input type="number" class="form-control" name="ausgabe" 
                                   step="0.01" min="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveEntry()">Speichern</button>
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
<script>
function showNewEntryModal() {
    const modal = new bootstrap.Modal(document.getElementById('entryModal'));
    modal.show();
}

function saveEntry() {
    const form = document.getElementById('quickEntryForm');
    const formData = new FormData(form);

    // Validierung
    const einnahme = parseFloat(formData.get('einnahme')) || 0;
    const ausgabe = parseFloat(formData.get('ausgabe')) || 0;

    if (!formData.get('datum')) {
        alert('Bitte ein Datum eingeben');
        return;
    }

    if (einnahme <= 0 && ausgabe <= 0) {
        alert('Bitte entweder eine Einnahme oder eine Ausgabe eingeben');
        return;
    }

    fetch('save_entry.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.message || 'Fehler beim Speichern des Eintrags');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten: ' + error.message);
    });
}

// Einnahme/Ausgabe-Felder gegenseitig deaktivieren
document.addEventListener('DOMContentLoaded', function() {
    const forms = ['quickEntryForm', 'editForm'];
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        const einnahmeField = form.querySelector('input[name="einnahme"]');
        const ausgabeField = form.querySelector('input[name="ausgabe"]');

        einnahmeField.addEventListener('input', function() {
            ausgabeField.disabled = this.value && this.value > 0;
        });

        ausgabeField.addEventListener('input', function() {
            einnahmeField.disabled = this.value && this.value > 0;
        });
    });
});

function editEntry(id) {
    fetch(`get_entry.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.entry.id;
                document.getElementById('edit_datum').value = data.entry.datum;
                document.getElementById('edit_beleg').value = data.entry.beleg;
                document.getElementById('edit_bemerkung').value = data.entry.bemerkung;
                document.getElementById('edit_einnahme').value = data.entry.einnahme || '';
                document.getElementById('edit_ausgabe').value = data.entry.ausgabe || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        });
}

// Automatische Aktualisierung bei Änderungen
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editForm');
    const einnahmeInput = editForm.querySelector('input[name="einnahme"]');
    const ausgabeInput = editForm.querySelector('input[name="ausgabe"]');
    
    // Event-Listener für Änderungen
    [einnahmeInput, ausgabeInput].forEach(input => {
        input.addEventListener('input', function() {
            // Automatisch nach kurzer Verzögerung speichern
            clearTimeout(input.saveTimeout);
            input.saveTimeout = setTimeout(() => {
                editForm.dispatchEvent(new Event('submit'));
            }, 500); // 500ms Verzögerung
        });
    });
});

// Bestehende updateEntry-Funktion überarbeiten
function updateEntry(event) {
    event.preventDefault();
    
    // Nur fortfahren wenn tatsächlich der Submit-Button geklickt wurde
    if (event.submitter?.type !== 'submit') {
        return;
    }

    const formData = new FormData(event.target);

    // Validierung
    const einnahme = formData.get('einnahme');
    const ausgabe = formData.get('ausgabe');

    if ((einnahme && ausgabe) || (parseFloat(einnahme) < 0 || parseFloat(ausgabe) < 0)) {
        alert('Bitte entweder nur Einnahme oder nur Ausgabe eingeben (positive Zahlen)');
        return;
    }

    console.log('Sende Update-Daten:', Object.fromEntries(formData));

    fetch('update_entry.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
            location.reload();
        } else {
            throw new Error(data.message || 'Fehler beim Aktualisieren des Eintrags');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten: ' + error.message);
    });
}

function deleteEntry(id) {
    if (confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
        fetch(`delete_entry.php?id=${id}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Fehler beim Löschen des Eintrags');
                }
            });
    }
}
</script>

<!-- Zusätzliche Styles -->
<style>
.container-fluid {
    padding: 20px;
    max-width: 1800px; /* oder die gewünschte maximale Breite */
    margin: 0 auto;
}

.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.card {
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

/* Verbesserte Abstände */
.table-responsive {
    margin-bottom: 2rem;
}

/* Verbesserte Text-Styles */
.fw-bold {
    font-weight: 600;
}
</style>

<!-- Bootstrap Bundle hinzufügen -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadStats() {
        const stats = ['eintraege_heute', 'umsatz_heute', 'eintraege_monat', 'umsatz_monat'];
        
        stats.forEach(stat => {
            fetch('get_stats.php?stat=' + stat)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const element = document.getElementById('stats_' + stat);
                        if (element) {
                            element.textContent = data.value;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }

    // Initial laden
    loadStats();
    
    // Alle 60 Sekunden aktualisieren
    setInterval(loadStats, 60000);
});
</script>

<!-- JavaScript für Select2 und Filter-Funktionalität -->
<script>
$(document).ready(function() {
    // Initialisiere Select2 mit Suchfunktion
    $('select[name="bemerkung"]').select2({
        placeholder: 'Bemerkung auswählen',
        allowClear: true,
        width: '100%',
        language: 'de',
        theme: 'bootstrap4',
        // Aktiviere die Suchfunktion
        minimumInputLength: 0,
        minimumResultsForSearch: 0
    });
});

function resetFilter() {
    const urlParams = new URLSearchParams(window.location.search);

    // Setze alle Formularfelder zurück
    document.getElementById('filterForm').reset();

    // Entferne nur die Filter-bezogenen Parameter
    urlParams.delete('von_datum');
    urlParams.delete('bis_datum');
    urlParams.delete('typ');
    urlParams.delete('bemerkung');

    // Aktualisiere die URL ohne die Seite neu zu laden
    history.replaceState(null, '', '?' + urlParams.toString());

    // Neu laden der Seite mit den ursprünglichen Parametern
    location.reload();
}

// Füge Click-Event für den Zurücksetzen-Button hinzu
$(document).ready(function() {
    $('.btn-secondary').on('click', function(e) {
        e.preventDefault();
        resetFilter();
    });
});
</script>

<!-- Fügen Sie die Select2 CSS und JS ein -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Fügen Sie die Select2 Bootstrap Theme CSS hinzu -->
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet">

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
