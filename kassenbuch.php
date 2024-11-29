<?php
session_start();
require_once 'includes/init.php';
require_once 'functions.php';     // Lädt die Authentifizierungsfunktionen
require_once 'includes/auth.php'; // Prüft nur den Login-Status

// Setze den Seitentitel
$page_title = $site_name ? "Kassenbuch - " . htmlspecialchars($site_name) : "Kassenbuch";

// Dann erst den Header einbinden
require_once 'includes/header.php';

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

    <!-- Bemerkungen Datalist -->
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

    <!-- Schnelleingabe-Formular -->
    <div class="quick-entry-form mb-4">
        <form id="quickEntryForm" class="row g-3">
            <div class="col-md-2">
                <label for="datum" class="form-label">Datum</label>
                <input type="date" class="form-control" id="datum" name="datum" 
                       value="<?= date('Y-m-d') ?>" required>
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
    </div>
    <?php endif; ?>

    <!-- Tabelle wird hier eingefügt -->
    <?php include 'includes/kassenbuch_table.php'; ?>
</div>

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

