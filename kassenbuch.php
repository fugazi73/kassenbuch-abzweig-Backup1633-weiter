<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Prüfe ob Benutzer das Kassenbuch sehen darf
if (!check_permission('view_cashbook')) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

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

// Verfügbare Monate laden
$monate_query = $conn->prepare("
    SELECT DISTINCT 
        YEAR(datum) as jahr,
        MONTH(datum) as monat,
        DATE_FORMAT(datum, '%m.%Y') as monat_jahr,
        DATE_FORMAT(datum, '%M %Y') as monat_name
    FROM kassenbuch_eintraege 
    WHERE bemerkung != 'Kassenstart'
    ORDER BY jahr DESC, monat DESC
");
$monate_query->execute();
$monate_result = $monate_query->get_result();
$monate = [];
while ($row = $monate_result->fetch_assoc()) {
    $monate[] = [
        'wert' => $row['monat_jahr'],
        'anzeige' => ucfirst(strftime('%B %Y', strtotime($row['jahr'] . '-' . $row['monat'] . '-01')))
    ];
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
            <?php if (check_permission('add_entries')): ?>
            <div class="quick-entry-section">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-plus-circle text-success"></i> Neuer Eintrag
                </h3>
                <div class="p-3 bg-body-tertiary rounded">
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
                                <input type="number" class="form-control einnahme-field" id="einnahme" name="einnahme" 
                                       step="0.01" min="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="ausgabe" class="form-label small">Ausgabe</label>
                            <div class="input-group">
                                <input type="number" class="form-control ausgabe-field" id="ausgabe" name="ausgabe" 
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
            <?php endif; ?>

            <!-- Filter-Bereich -->
            <?php if (check_permission('filter_month') || 
                      check_permission('filter_remarks') || 
                      check_permission('filter_date') || 
                      check_permission('filter_type')): ?>
            <div class="filter-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-funnel text-info"></i> Filter
                </h3>
                <form id="filterForm" method="GET" class="row g-3">
                    <?php if (check_permission('filter_month')): ?>
                    <div class="col-md-2">
                        <label class="form-label small">Monat</label>
                        <select name="monat" class="form-select">
                            <option value="">Alle Monate</option>
                            <?php foreach ($monate as $monat): ?>
                                <option value="<?= htmlspecialchars($monat['wert']) ?>" 
                                        <?= ($_GET['monat'] ?? '') === $monat['wert'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($monat['anzeige']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if (check_permission('filter_remarks')): ?>
                    <div class="col-md-2">
                        <label class="form-label small">Bemerkung</label>
                        <select name="bemerkung" class="form-select select2-filter">
                            <option value="">Alle</option>
                            <?php foreach ($bemerkungen as $bemerkung): ?>
                                <option value="<?= htmlspecialchars($bemerkung) ?>"
                                        <?= ($_GET['bemerkung'] ?? '') === $bemerkung ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bemerkung) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if (check_permission('filter_date')): ?>
                    <div class="col-md-2">
                        <label class="form-label small">Von Datum</label>
                        <input type="text" name="von_datum" class="form-control" 
                               placeholder="TT.MM.JJJJ"
                               value="<?= isset($_GET['von_datum']) ? htmlspecialchars($_GET['von_datum']) : '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bis Datum</label>
                        <input type="text" name="bis_datum" class="form-control" 
                               placeholder="TT.MM.JJJJ"
                               value="<?= isset($_GET['bis_datum']) ? htmlspecialchars($_GET['bis_datum']) : '' ?>">
                    </div>
                    <?php endif; ?>

                    <?php if (check_permission('filter_type')): ?>
                    <div class="col-md-2">
                        <label class="form-label small">Typ</label>
                        <select name="typ" class="form-select">
                            <option value="">Alle</option>
                            <option value="einnahme" <?= ($_GET['typ'] ?? '') === 'einnahme' ? 'selected' : '' ?>>Einnahmen</option>
                            <option value="ausgabe" <?= ($_GET['typ'] ?? '') === 'ausgabe' ? 'selected' : '' ?>>Ausgaben</option>
                        </select>
                    </div>
                    <?php endif; ?>

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

<!-- jQuery und Select2 CSS/JS einbinden -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        createTag: function(params) {
            return {
                id: params.term,
                text: params.term,
                newOption: true
            };
        },
        templateResult: function(data) {
            var $result = $("<span></span>");
            if (data.newOption) {
                $result.append('<i class="bi bi-plus-circle me-2"></i>' + data.text);
            } else {
                $result.text(data.text);
            }
            return $result;
        },
        language: {
            noResults: function() {
                return "Keine Ergebnisse gefunden";
            },
            searching: function() {
                return "Suche...";
            }
        }
    });

    // Dark Mode Anpassung für Select2
    function updateSelect2Theme() {
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            $('.select2-container--bootstrap-5 .select2-selection').css({
                'background-color': '#2b3035',
                'border-color': 'rgba(255,255,255,.125)',
                'color': '#fff'
            });
            $('.select2-container--bootstrap-5 .select2-dropdown').css({
                'background-color': '#2b3035',
                'border-color': 'rgba(255,255,255,.125)',
                'color': '#fff'
            });
            $('.select2-container--bootstrap-5 .select2-search__field').css({
                'background-color': '#2b3035',
                'color': '#fff',
                'border-color': 'rgba(255,255,255,.125)'
            });
        }
    }

    // Initial theme update
    updateSelect2Theme();
    updateReadonlyStyle();

    // Update theme when it changes
    const themeObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-bs-theme') {
                updateSelect2Theme();
                updateReadonlyStyle();
            }
        });
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme']
    });

    // Select2 für Filter-Bemerkungen
    $('.select2-filter').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: 'Alle Bemerkungen',
        language: {
            noResults: function() {
                return "Keine Ergebnisse gefunden";
            }
        }
    });

    // Monatsfilter Handling
    $('select[name="monat"]').on('change', function() {
        const monat = $(this).val();
        if (monat) {
            const [month, year] = monat.split('.');
            const startDate = `01.${monat}`;
            const endDate = new Date(year, month, 0).getDate() + `.${monat}`;
            $('input[name="von_datum"]').val(startDate);
            $('input[name="bis_datum"]').val(endDate);
        } else {
            $('input[name="von_datum"]').val('');
            $('input[name="bis_datum"]').val('');
        }
    });

    // Bootstrap Modal Handling
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    
    // Bearbeiten-Funktion
    window.editEntry = function(id) {
        // Lade die Daten des Eintrags
        $.ajax({
            url: 'api/edit_entry.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response && response.success && response.entry) {
                    // Fülle das Modal mit den Daten
                    const entry = response.entry;
                    const form = $('#editEntryForm');
                    form.find('[name="id"]').val(entry.id);
                    form.find('[name="datum"]').val(entry.datum);
                    form.find('[name="beleg_nr"]').val(entry.beleg_nr);
                    form.find('[name="bemerkung"]').val(entry.bemerkung);
                    form.find('[name="einnahme"]').val(entry.einnahme || '0.00');
                    form.find('[name="ausgabe"]').val(entry.ausgabe || '0.00');
                    
                    // Zeige das Modal
                    editModal.show();
                } else {
                    showAlert(response?.message || 'Fehler beim Laden des Eintrags');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Fehler', 'Fehler beim Laden des Eintrags: ' + (error || 'Unbekannter Fehler'));
            }
        });
    };

    // Speichern der Änderungen
    $('#editEntryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'api/edit_entry.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    editModal.hide();
                    // Entferne alle Modal-Backdrops
                    $('.modal-backdrop').remove();
                    // Aktualisiere die Seite nach kurzer Verzögerung
                    setTimeout(() => {
                        location.reload();
                    }, 100);
                } else {
                    showAlert(response?.message || 'Fehler beim Speichern');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Fehler', 'Fehler beim Speichern: ' + (error || 'Unbekannter Fehler'));
            }
        });
    });

    // Löschen-Funktion
    window.deleteEntry = function(id) {
        if (confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
            $.ajax({
                url: 'api/delete_entry.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        // Aktualisiere die Seite nach kurzer Verzögerung
                        setTimeout(() => {
                            location.reload();
                        }, 100);
                    } else {
                        showAlert(response?.message || 'Fehler beim Löschen');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Fehler', 'Fehler beim Löschen: ' + (error || 'Unbekannter Fehler'));
                }
            });
        }
    };

    // Fehleranzeige-Funktion
    function showError(title, message) {
        const errorHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>${title}:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        `;
        // Füge die Fehlermeldung am Anfang der Tabelle ein
        $('.table-responsive').prepend(errorHtml);
    }

    // Modal Event Listener
    $('#editModal').on('hidden.bs.modal', function () {
        // Entferne alle Modal-Backdrops
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });

    // Massenauswahl-Funktionalität
    $('#selectAll').on('change', function() {
        $('.entry-checkbox').prop('checked', $(this).prop('checked'));
        updateMassDeleteButton();
    });

    $('#selectCurrentPage').on('click', function(e) {
        e.preventDefault();
        $('.entry-checkbox').prop('checked', true);
        updateMassDeleteButton();
    });

    $('#selectAllPages').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'get_all_entry_ids.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $('.entry-checkbox').prop('checked', true);
                    updateMassDeleteButton(response.total);
                }
            }
        });
    });

    $('#deselectAll').on('click', function(e) {
        e.preventDefault();
        $('.entry-checkbox').prop('checked', false);
        updateMassDeleteButton();
    });

    $('.entry-checkbox').on('change', function() {
        updateMassDeleteButton();
    });

    function updateMassDeleteButton() {
        const checkedCount = $('.entry-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#massDeleteBtn').show().find('.delete-count').text(
                checkedCount + ' Einträge löschen'
            );
        } else {
            $('#massDeleteBtn').hide();
        }
    }

    $('#massDeleteBtn').on('click', function() {
        const selectedIds = $('.entry-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        if (confirm('Möchten Sie die ausgewählten Einträge wirklich löschen?')) {
            $.ajax({
                url: 'mass_delete_entries.php',
                type: 'POST',
                data: { ids: selectedIds },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 100);
                    } else {
                        showAlert(response?.message || 'Fehler beim Löschen');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Fehler', 'Fehler beim Löschen der Einträge: ' + (error || 'Unbekannter Fehler'));
                }
            });
        }
    });

    // Funktion zur Validierung von Einnahmen und Ausgaben
    function validateEinnahmeAusgabe(einnahmeField, ausgabeField) {
        const einnahme = parseFloat(einnahmeField.val()) || 0;
        const ausgabe = parseFloat(ausgabeField.val()) || 0;

        if (einnahme > 0 && ausgabe > 0) {
            showAlert('Ein Eintrag kann nicht gleichzeitig Einnahme und Ausgabe sein');
            return false;
        }

        if (einnahme <= 0 && ausgabe <= 0) {
            showAlert('Bitte geben Sie entweder eine Einnahme oder eine Ausgabe ein');
            return false;
        }

        return true;
    }

    // Funktion zum Aktualisieren der Feld-Zustände
    function updateFieldStates(activeField, inactiveField) {
        const activeValue = parseFloat(activeField.val()) || 0;
        if (activeValue > 0) {
            inactiveField.prop('readonly', true)
                        .css('background-color', document.documentElement.getAttribute('data-bs-theme') === 'dark' 
                            ? 'var(--bs-gray-700)' 
                            : 'var(--bs-gray-200)')
                        .attr('tabindex', '-1');
        } else {
            inactiveField.prop('readonly', false)
                        .css('background-color', '')
                        .removeAttr('tabindex');
        }
    }

    // Event-Handler für Einnahme-Feld
    $('input[name="einnahme"]').on('input', function() {
        const einnahmeField = $(this);
        const ausgabeField = einnahmeField.closest('form').find('input[name="ausgabe"]');
        updateFieldStates(einnahmeField, ausgabeField);
    });

    // Event-Handler für Ausgabe-Feld
    $('input[name="ausgabe"]').on('input', function() {
        const ausgabeField = $(this);
        const einnahmeField = ausgabeField.closest('form').find('input[name="einnahme"]');
        updateFieldStates(ausgabeField, einnahmeField);
    });

    // Initialisierung der Felder beim Laden des Modals
    $('#editModal').on('shown.bs.modal', function() {
        const form = $(this).find('form');
        const einnahmeField = form.find('input[name="einnahme"]');
        const ausgabeField = form.find('input[name="ausgabe"]');
        
        // Prüfe initial welches Feld aktiv ist
        if (parseFloat(einnahmeField.val()) > 0) {
            updateFieldStates(einnahmeField, ausgabeField);
        } else if (parseFloat(ausgabeField.val()) > 0) {
            updateFieldStates(ausgabeField, einnahmeField);
        }
    });

    // Dark Mode Anpassungen für readonly Felder
    function updateReadonlyStyle() {
        const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        $('input[readonly]').css('background-color', 
            isDarkMode ? 'var(--bs-gray-700)' : 'var(--bs-gray-200)'
        );
    }

    // Quick Entry Form Submit
    $('#quickEntryForm').on('submit', function(e) {
        e.preventDefault();
        
        const einnahmeField = $(this).find('input[name="einnahme"]');
        const ausgabeField = $(this).find('input[name="ausgabe"]');

        if (!validateEinnahmeAusgabe(einnahmeField, ausgabeField)) {
            return;
        }

        // Rest des bestehenden Submit-Codes
        // ... existing code ...
    });

    // Edit Form Submit
    $('#editEntryForm').on('submit', function(e) {
        e.preventDefault();
        
        const einnahmeField = $(this).find('input[name="einnahme"]');
        const ausgabeField = $(this).find('input[name="ausgabe"]');

        if (!validateEinnahmeAusgabe(einnahmeField, ausgabeField)) {
            return;
        }

        // Rest des bestehenden Submit-Codes
        // ... existing code ...
    });
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

/* Neue Styles für Ein-/Ausgabe Felder */
.einnahme-field {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.ausgabe-field {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

/* Dark mode Anpassungen für die Felder */
[data-bs-theme="dark"] .einnahme-field {
    background-color: rgba(25, 135, 84, 0.15) !important;
    color: #fff !important;
}

[data-bs-theme="dark"] .ausgabe-field {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #fff !important;
}

/* Neuer Eintrag Bereich */
.quick-entry-form {
    background-color: var(--bs-light);
    border: 1px solid var(--bs-border-color);
}

[data-bs-theme="dark"] .quick-entry-form {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

[data-bs-theme="dark"] .input-group-text {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

[data-bs-theme="dark"] .form-control:focus,
[data-bs-theme="dark"] .form-select:focus {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.25);
    color: #fff;
}

/* Select2 Dark Mode Anpassungen */
[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single {
    background-color: rgba(255, 255, 255, 0.05) !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
    color: #fff !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown {
    background-color: #2b3035 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-search__field {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
    background-color: transparent !important;
    color: #fff !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option[aria-selected=true] {
    background-color: rgba(255, 255, 255, 0.15) !important;
}

/* Dark mode table adjustments */
[data-bs-theme="dark"] .table {
    --bs-table-striped-bg: rgba(255, 255, 255, 0.03);
    --bs-table-hover-bg: rgba(255, 255, 255, 0.075);
}

[data-bs-theme="dark"] .btn-group .btn {
    border-color: rgba(255, 255, 255, 0.1);
}

/* Styles für readonly Felder */
input[readonly] {
    cursor: not-allowed;
    opacity: 0.7;
}

[data-bs-theme="dark"] input[readonly] {
    background-color: var(--bs-gray-700) !important;
    border-color: var(--bs-gray-600) !important;
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

