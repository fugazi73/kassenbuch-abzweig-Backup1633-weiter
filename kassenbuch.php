<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}
check_login();

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

$page_title = 'Übersicht | Kassenbuch';
require_once 'includes/header.php';
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

<<<<<<< HEAD
    <!-- Datalist für Bemerkungen -->
=======
    <!-- Bemerkungen Datalist -->
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
    <datalist id="bemerkungen">
        <?php 
        $stmt = $conn->prepare("
            SELECT DISTINCT bemerkung 
            FROM kassenbuch_eintraege 
            WHERE bemerkung != 'Kassenstart' 
            AND bemerkung IS NOT NULL 
            ORDER BY bemerkung ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($row['bemerkung']) ?>">
        <?php endwhile; ?>
    </datalist>

<<<<<<< HEAD
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
=======
    <!-- Schnelleingabe-Formular -->
    <div class="quick-entry-form mb-4">
        <form id="quickEntryForm" class="row g-3">
            <div class="col-md-2">
                <label for="datum" class="form-label">Datum</label>
                <input type="date" class="form-control" id="datum" name="datum" 
                       value="<?= date('Y-m-d') ?>" required>
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
            </div>
            <div class="col-md-4">
                <label for="bemerkung" class="form-label">Bemerkung</label>
                <input type="text" class="form-control" id="bemerkung" 
                       name="bemerkung" required 
                       list="bemerkungen" 
                       autocomplete="off">
            </div>
            <div class="col-md-2">
                <label for="einnahme" class="form-label">Einnahme</label>
                <input type="number" class="form-control" id="einnahme" name="einnahme" 
                       step="0.01" min="0">
            </div>
            <div class="col-md-2">
                <label for="ausgabe" class="form-label">Ausgabe</label>
                <input type="number" class="form-control" id="ausgabe" name="ausgabe" 
                       step="0.01" min="0">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Speichern</button>
            </div>
        </form>
    </div>

<<<<<<< HEAD
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
=======
    <!-- Filter nur für Admin/Chef -->
    <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
    <div class="filter-container">
        <form id="filterForm" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Von Datum</label>
                <input type="date" name="von_datum" class="form-control" 
                       value="<?= $_GET['von_datum'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bis Datum</label>
                <input type="date" name="bis_datum" class="form-control" 
                       value="<?= $_GET['bis_datum'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Typ</label>
                <select name="typ" class="form-select">
                    <option value="">Alle</option>
                    <option value="einnahme" <?= ($_GET['typ'] ?? '') === 'einnahme' ? 'selected' : '' ?>>Einnahmen</option>
                    <option value="ausgabe" <?= ($_GET['typ'] ?? '') === 'ausgabe' ? 'selected' : '' ?>>Ausgaben</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filtern</button>
                <a href="kassenbuch.php" class="btn btn-secondary ms-2">Zurücksetzen</a>
            </div>
        </form>
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
    </div>
    <?php endif; ?>

    <!-- Tabelle wird hier eingefügt -->
    <?php include 'includes/kassenbuch_table.php'; ?>
</div>

<<<<<<< HEAD
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
=======
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
>>>>>>> 8a89f0d (neuster stand dynamishce tabelle werde hinzugefügt erste teile vorhanden)
</div>

<?php if (in_array($_SESSION['user_role'], ['admin', 'chef'])): ?>
    <a href="import.php" class="btn btn-success ms-2">
        <i class="bi bi-file-earmark-excel"></i> Excel Import
    </a>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

