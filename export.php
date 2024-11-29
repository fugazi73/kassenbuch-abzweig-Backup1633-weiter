<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Export | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-download me-2"></i>
                <h6 class="mb-0">Daten exportieren</h6>
            </div>
            
            <form method="get" action="generate_export.php">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Von</label>
                        <input type="date" class="form-control" name="von" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Bis</label>
                        <input type="date" class="form-control" name="bis" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Format</label>
                        <select class="form-select" name="format" required>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Export erstellen
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-clock-history me-2"></i>
                <h6 class="mb-0">Export-Historie</h6>
            </div>

            <div class="list-group">
                <?php
                // Prüfe ob die Tabelle existiert
                $result = $conn->query("SHOW TABLES LIKE 'export_history'");
                if($result->num_rows > 0):
                    $sql = "SELECT * FROM export_history ORDER BY created_at DESC LIMIT 10";
                    $result = $conn->query($sql);
                    
                    if($result && $result->num_rows > 0):
                        while($row = $result->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-muted">
                                            <?= date('d.m.Y H:i', strtotime($row['created_at'])) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($row['date_from']) ?> - 
                                            <?= htmlspecialchars($row['date_to']) ?> 
                                            (<?= strtoupper($row['format']) ?>)
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <a href="exports/<?= $row['filename'] ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="exports/<?= $row['filename'] ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           download>
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div class="text-muted small">Keine Export-Historie verfügbar.</div>
                    <?php endif;
                endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
