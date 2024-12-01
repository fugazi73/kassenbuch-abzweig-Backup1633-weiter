<?php
session_start();
require_once 'includes/init.php';
require_once 'functions.php';     // Lädt die Authentifizierungsfunktionen
require_once 'includes/auth.php'; // Prüft nur den Login-Status

// Setze den Seitentitel
$page_title = $site_name ? "Kassenbuch - " . htmlspecialchars($site_name) : "Kassenbuch";

// Startbetrag und laufenden Kassenstand berechnen
$sql = "SELECT 
    (SELECT COALESCE(einnahme, 0) 
     FROM kassenbuch_eintraege 
     WHERE bemerkung = 'Kassenstart' 
     ORDER BY datum DESC, id DESC 
     LIMIT 1) as startbetrag,
    (SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0) 
     FROM kassenbuch_eintraege) as gesamt_kassenstand";

$result = $conn->query($sql);
$kasseninfo = $result->fetch_assoc();
$startbetrag = $kasseninfo['startbetrag'];
$current_kassenstand = $kasseninfo['gesamt_kassenstand'];

// Hole Kassenstart-Eintrag
$kassenstart_query = $conn->query("
    SELECT einnahme as betrag, DATE_FORMAT(datum, '%d.%m.%Y') as datum 
    FROM kassenbuch_eintraege 
    WHERE bemerkung = 'Kassenstart' 
    ORDER BY datum DESC 
    LIMIT 1
");
$kassenstart = $kassenstart_query->fetch_assoc();

// SQL für Filter erweitern
$sql = "SELECT * FROM kassenbuch_eintraege WHERE 1=1";

// Nur Admins sehen den Kassenstart-Eintrag
if (!is_admin() && !is_chef()) {
    $sql .= " AND bemerkung != 'Kassenstart'";
}

// Filter-Parameter
$params = [];
$types = "";

// Datum-Filter
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

// Bemerkungen für Select laden
$bemerkungen_query = $conn->prepare("
    SELECT DISTINCT bemerkung 
    FROM kassenbuch_eintraege 
    WHERE bemerkung != 'Kassenstart' 
    AND bemerkung IS NOT NULL 
    AND bemerkung != ''
    ORDER BY bemerkung ASC
");
$bemerkungen_query->execute();
$bemerkungen_result = $bemerkungen_query->get_result();
$bemerkungen = [];
while ($row = $bemerkungen_result->fetch_assoc()) {
    $bemerkungen[] = $row['bemerkung'];
}

// Header einbinden
require_once 'includes/header.php';

?>

<!-- Haupt-Container -->
<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Header-Bereich mit hervorgehobenem Kassenstand -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 card-title mb-0">
                    <i class="bi bi-journal-text text-primary"></i> Kassenbuch
                </h1>
                <div class="kassenstand-box text-center">
                    <div class="text-muted small mb-1">Aktueller Kassenstand</div>
                    <div class="kassenstand-value <?= $current_kassenstand >= 0 ? 'text-success' : 'text-danger' ?> h3 mb-0">
                        <?= number_format($current_kassenstand, 2, ',', '.') ?> €
                    </div>
                    <?php if (is_admin() || is_chef()): ?>
                    <div class="text-muted small mt-1">
                        Stand: <?= $kassenstart['datum'] ?? date('d.m.Y') ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hervorgehobener Eintragsbereich -->
            <div class="quick-entry-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-plus-circle text-success"></i> Neuer Eintrag
                </h3>
                <div class="quick-entry-form p-3 border border-danger rounded bg-danger bg-opacity-10">
                    <form id="quickEntryForm" class="row g-3">
                        <div class="col-md-2">
                            <label for="datum" class="form-label small">Datum</label>
                            <input type="date" class="form-control" id="datum" name="datum" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="bemerkung" class="form-label small">Bemerkung</label>
                            <select class="form-select" id="bemerkung" name="bemerkung" required>
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($bemerkungen as $bemerkung): ?>
                                    <option value="<?= htmlspecialchars($bemerkung) ?>">
                                        <?= htmlspecialchars($bemerkung) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="einnahme" class="form-label small">Einnahme</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="einnahme" name="einnahme" 
                                       step="0.01" min="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="ausgabe" class="form-label small">Ausgabe</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="ausgabe" name="ausgabe" 
                                       step="0.01" min="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Hinzufügen
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter nur für Admin/Chef -->
            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
            <div class="filter-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-funnel text-info"></i> Filter
                </h3>
                <form id="filterForm" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Von Datum</label>
                        <input type="text" name="von_datum" class="form-control" 
                               placeholder="TT.MM.JJJJ"
                               value="<?= isset($_GET['von_datum']) ? htmlspecialchars($_GET['von_datum']) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Bis Datum</label>
                        <input type="text" name="bis_datum" class="form-control" 
                               placeholder="TT.MM.JJJJ"
                               value="<?= isset($_GET['bis_datum']) ? htmlspecialchars($_GET['bis_datum']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Typ</label>
                        <select name="typ" class="form-select">
                            <option value="">Alle</option>
                            <option value="einnahme" <?= ($_GET['typ'] ?? '') === 'einnahme' ? 'selected' : '' ?>>Einnahmen</option>
                            <option value="ausgabe" <?= ($_GET['typ'] ?? '') === 'ausgabe' ? 'selected' : '' ?>>Ausgaben</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bemerkung</label>
                        <input type="text" name="bemerkung" class="form-control"
                               list="bemerkungen"
                               value="<?= isset($_GET['bemerkung']) ? htmlspecialchars($_GET['bemerkung']) : '' ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel-fill"></i> Filtern
                        </button>
                        <a href="kassenbuch.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Tabelle -->
            <?php include 'includes/kassenbuch_table.php'; ?>
        </div>
    </div>
</div>

<!-- Select2 CSS und JS einbinden -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Select2 für Bemerkungen initialisieren
    $('#bemerkung').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: 'Bemerkung auswählen oder eingeben',
        tags: true,
        language: {
            noResults: function() {
                return "Keine Ergebnisse gefunden";
            }
        }
    });

    // Dark Mode Anpassung
    if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
        $('.select2-container--bootstrap-5 .select2-selection').css({
            'background-color': '#2b3035',
            'border-color': 'rgba(255,255,255,.125)',
            'color': '#fff'
        });
    }
});
</script>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
}

.kassenstand-box {
    background: var(--bs-light);
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

[data-bs-theme="dark"] .kassenstand-box {
    background: rgba(255, 255, 255, 0.05);
}

.kassenstand-value {
    font-weight: 600;
    line-height: 1;
}

.quick-entry-form {
    transition: all 0.3s ease;
}

.quick-entry-form:focus-within {
    border-color: var(--bs-primary) !important;
    background-color: rgba(var(--bs-danger-rgb), 0.15) !important;
    box-shadow: 0 0.125rem 0.25rem rgba(var(--bs-danger-rgb), 0.1);
}

h3 {
    font-size: 0.95rem;
    font-weight: 500;
}

.form-label.small {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

[data-bs-theme="dark"] .card {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .bg-danger.bg-opacity-10 {
    background-color: rgba(var(--bs-danger-rgb), 0.15) !important;
}

.input-group-text {
    font-size: 0.9rem;
}

/* Dropdown Styling */
input[list]::-webkit-calendar-picker-indicator {
    display: none !important;
}

input[list] {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

/* Dark mode anpassungen */
[data-bs-theme="dark"] input[list] {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
}

/* Dropdown Liste */
datalist {
    position: absolute;
    max-height: 300px;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
    background-color: white;
    overflow-y: auto;
    z-index: 1000;
}

datalist option {
    padding: 0.5rem 1rem;
    cursor: pointer;
}

datalist option:hover {
    background-color: #f8f9fa;
    color: #16181b;
}

[data-bs-theme="dark"] datalist {
    background-color: #2b3035;
    border-color: rgba(255,255,255,.125);
}

[data-bs-theme="dark"] datalist option {
    color: #fff;
}

[data-bs-theme="dark"] datalist option:hover {
    background-color: #3d4246;
    color: #fff;
}

/* Select2 Anpassungen */
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
}

.select2-container--bootstrap-5 .select2-selection--single {
    padding-top: 4px;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    top: 4px;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown {
    background-color: #2b3035;
    border-color: rgba(255,255,255,.125);
    color: #fff;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
    color: #fff;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #3d4246;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-search__field {
    background-color: #2b3035;
    color: #fff;
    border-color: rgba(255,255,255,.125);
}
</style>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eintrag bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEntryForm">
                <input type="hidden" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Datum</label>
                        <input type="date" name="datum" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beleg-Nr.</label>
                        <input type="text" name="beleg_nr" class="form-control" maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bemerkung</label>
                        <input type="text" name="bemerkung" 
                               class="form-control" required 
                               list="bemerkungen" 
                               autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Einnahme</label>
                        <input type="number" name="einnahme" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ausgabe</label>
                        <input type="number" name="ausgabe" class="form-control" step="0.01" min="0">
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

<?php require_once 'includes/footer.php'; ?>

