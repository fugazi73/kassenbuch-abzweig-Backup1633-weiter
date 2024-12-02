<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Prüfe Exportberechtigung
if (!check_permission('export')) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

$page_title = "Export - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';
?>

<!-- Bootstrap JS für Dropdowns -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

<!-- Rest des Export-Codes -->

<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

if (!is_admin()) {
    handle_forbidden();
}

// Löschfunktion
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Hole den Dateinamen bevor wir den Eintrag löschen
    $sql = "SELECT filename FROM export_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    
    if ($file) {
        // Lösche die physische Datei wenn sie existiert
        $filepath = 'exports/' . $file['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Lösche den Datenbankeintrag
        $sql = "DELETE FROM export_history WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        header("Location: export.php?msg=deleted");
        exit;
    }
}

// Download-Funktion
if (isset($_GET['download']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT filename FROM export_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    
    if ($file) {
        $filepath = 'exports/' . $file['filename'];
        if (file_exists($filepath)) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            
            switch($ext) {
                case 'pdf':
                    header('Content-Type: application/pdf');
                    break;
                case 'xlsx':
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    break;
                case 'csv':
                    header('Content-Type: text/csv');
                    break;
                default:
                    die('Ungültiges Dateiformat');
            }
            
            header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }
    die('Datei nicht gefunden');
}

// Vorschau-Funktion
if (isset($_GET['preview']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT filename FROM export_history WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    
    if ($file) {
        $filepath = 'exports/' . $file['filename'];
        if (file_exists($filepath)) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            
            switch($ext) {
                case 'pdf':
                    header('Content-Type: application/pdf');
                    break;
                case 'xlsx':
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    break;
                case 'csv':
                    header('Content-Type: text/csv');
                    break;
                default:
                    die('Ungültiges Dateiformat');
            }
            
            header('Content-Disposition: inline; filename="' . $file['filename'] . '"');
            readfile($filepath);
            exit;
        }
    }
    die('Datei nicht gefunden');
}

$page_title = 'Export | Kassenbuch';
require_once 'includes/header.php';

// Verfügbare Jahre aus der Datenbank ermitteln (mindestens 2023)
$years_query = $conn->query("
    SELECT DISTINCT YEAR(datum) as year 
    FROM kassenbuch_eintraege 
    UNION 
    SELECT 2023 as year 
    ORDER BY year DESC
");
$available_years = [];
while ($row = $years_query->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// Aktuelles Jahr und Monat
$current_year = date('Y');
$current_month = date('n');

// Monate mit Einträgen ermitteln
$months_query = $conn->prepare("
    SELECT DISTINCT 
        MONTH(datum) as month,
        COUNT(*) as entry_count
    FROM kassenbuch_eintraege 
    WHERE YEAR(datum) = ?
    GROUP BY MONTH(datum)
    ORDER BY month ASC
");
$months_query->bind_param('i', $current_year);
$months_query->execute();
$months_result = $months_query->get_result();
$active_months = [];
while ($row = $months_result->fetch_assoc()) {
    $active_months[$row['month']] = $row['entry_count'];
}
?>

<div class="max-width-container py-4">
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Export wurde erfolgreich gelöscht.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-download text-primary"></i> Export
            </h1>

            <!-- Schnellauswahl -->
            <div class="export-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-calendar3 text-success"></i> Schnellauswahl
                </h3>
                
                <!-- Jahr Auswahl -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <?php foreach ($available_years as $year): ?>
                        <button type="button" 
                                class="btn <?= $year == $current_year ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm year-select"
                                data-year="<?= $year ?>">
                            <?= $year ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Monatsübersicht -->
                <div class="row g-3 mb-4" id="monthsGrid">
                    <?php 
                    $months = [
                        1 => 'Januar', 2 => 'Februar', 3 => 'März',
                        4 => 'April', 5 => 'Mai', 6 => 'Juni',
                        7 => 'Juli', 8 => 'August', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                    ];
                    
                    foreach ($months as $month_num => $month_name):
                        $is_future = ($current_year == $year && $month_num > $current_month);
                        $has_entries = isset($active_months[$month_num]);
                        $entry_count = $active_months[$month_num] ?? 0;
                        
                        // Überspringen wenn keine Einträge und nicht aktuelles/zukünftiges Monat
                        if (!$has_entries && !$is_future) continue;
                        
                        $disabled = $is_future ? 'disabled' : '';
                        $opacity = (!$has_entries || $is_future) ? 'opacity-50' : '';
                    ?>
                        <div class="col-md-3">
                            <div class="card h-100 <?= $disabled ? 'bg-light' : 'hover-shadow' ?> <?= $opacity ?>">
                                <button type="button" 
                                        class="btn btn-link text-decoration-none p-3 text-start month-select" 
                                        data-month="<?= $month_num ?>"
                                        <?= $disabled ?>>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title small mb-1"><?= $month_name ?></h5>
                                            <div class="text-muted small">
                                                01.<?= str_pad($month_num, 2, '0', STR_PAD_LEFT) ?>.<?= $current_year ?> - 
                                                <?= date('t', strtotime("$current_year-$month_num-01")) ?>.<?= str_pad($month_num, 2, '0', STR_PAD_LEFT) ?>.<?= $current_year ?>
                                            </div>
                                        </div>
                                        <?php if ($has_entries): ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $entry_count ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Export ganzes Jahr -->
                <button type="button" class="btn btn-primary" id="exportYear">
                    <i class="bi bi-download"></i> Ganzes Jahr exportieren
                </button>
            </div>

            <!-- Export Historie -->
            <div class="export-section">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-clock-history text-info"></i> Export-Historie
                </h3>
                <div class="list-group">
                    <?php
                    $result = $conn->query("SHOW TABLES LIKE 'export_history'");
                    if($result->num_rows > 0):
                        $sql = "SELECT * FROM export_history ORDER BY created_at DESC LIMIT 10";
                        $result = $conn->query($sql);
                        
                        if($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()): 
                                $date_from = date('d.m.Y', strtotime($row['date_from']));
                                $date_to = date('d.m.Y', strtotime($row['date_to']));
                                $created_at = date('d.m.Y H:i', strtotime($row['created_at']));
                            ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small"><?= $created_at ?></div>
                                        <div>
                                            <?= $date_from ?> - <?= $date_to ?>
                                            <span class="badge bg-secondary ms-2"><?= strtoupper($row['format']) ?></span>
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <a href="export.php?preview=1&id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           target="_blank"
                                           title="Vorschau">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="export.php?download=1&id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="export.php" method="post" style="display: inline;" 
                                              onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Export löschen möchten?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="list-group-item text-muted text-center py-3">
                                <i class="bi bi-info-circle me-2"></i>
                                Keine Export-Historie verfügbar
                            </div>
                        <?php endif;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery einbinden -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-download"></i> Export erstellen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="generate_export.php" method="get">
                    <input type="hidden" name="von" id="exportVon">
                    <input type="hidden" name="bis" id="exportBis">
                    
                    <div class="mb-3">
                        <label class="form-label small">Zeitraum</label>
                        <div id="exportDateRange" class="form-control-plaintext"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Format</label>
                        <select class="form-select" name="format" required>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" form="exportForm" class="btn btn-primary btn-sm">
                    <i class="bi bi-download"></i> Exportieren
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal initialisieren
    const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
    let selectedYear = <?= $current_year ?>;

    // Jahr auswählen
    document.querySelectorAll('.year-select').forEach(button => {
        button.addEventListener('click', function() {
            selectedYear = this.dataset.year;
            document.querySelectorAll('.year-select').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
            
            // AJAX Aufruf für neue Monate
            fetch('get_active_months.php?year=' + selectedYear)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('monthsGrid').innerHTML = data;
                    updateMonthsGrid();
                });
        });
    });

    // Monat auswählen
    document.addEventListener('click', function(e) {
        if (e.target.closest('.month-select:not(:disabled)')) {
            const button = e.target.closest('.month-select');
            const month = button.dataset.month;
            const startDate = `01.${String(month).padStart(2, '0')}.${selectedYear}`;
            const lastDay = new Date(selectedYear, month, 0).getDate();
            const endDate = `${lastDay}.${String(month).padStart(2, '0')}.${selectedYear}`;
            
            document.getElementById('exportDateRange').textContent = `${startDate} - ${endDate}`;
            document.getElementById('exportVon').value = startDate;
            document.getElementById('exportBis').value = endDate;
            exportModal.show();
        }
    });

    // Ganzes Jahr exportieren
    document.getElementById('exportYear').addEventListener('click', function() {
        const startDate = `01.01.${selectedYear}`;
        const endDate = `31.12.${selectedYear}`;
        
        document.getElementById('exportDateRange').textContent = `${startDate} - ${endDate}`;
        document.getElementById('exportVon').value = startDate;
        document.getElementById('exportBis').value = endDate;
        exportModal.show();
    });

    function updateMonthsGrid() {
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;
        
        document.querySelectorAll('.month-select').forEach(button => {
            const month = button.dataset.month;
            const isFuture = selectedYear == currentYear && month > currentMonth;
            
            if (isFuture) {
                button.disabled = true;
                button.closest('.card').classList.add('bg-light', 'opacity-50');
                button.closest('.card').classList.remove('hover-shadow');
            } else {
                button.disabled = false;
                button.closest('.card').classList.remove('bg-light', 'opacity-50');
                button.closest('.card').classList.add('hover-shadow');
            }
        });
    }
});
</script>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
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

.hover-shadow {
    transition: all 0.2s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 .25rem .5rem rgba(0,0,0,.1)!important;
    transform: translateY(-1px);
}

.month-select {
    width: 100%;
    height: 100%;
    border: none;
    background: none;
    cursor: pointer;
}

.month-select:disabled {
    cursor: not-allowed;
}

[data-bs-theme="dark"] .bg-light {
    background-color: rgba(255, 255, 255, 0.05) !important;
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
