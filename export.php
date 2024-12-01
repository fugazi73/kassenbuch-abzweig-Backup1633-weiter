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

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-download text-primary"></i> Export
            </h1>

            <!-- Export-Formular -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-file-earmark-arrow-down text-success"></i> Daten exportieren
                </h3>
                <form method="get" action="generate_export.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Von</label>
                            <input type="date" class="form-control" name="von" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Bis</label>
                            <input type="date" class="form-control" name="bis" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Format</label>
                            <select class="form-select" name="format" required>
                                <option value="xlsx">Excel (XLSX)</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-download"></i> Export erstellen
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export-Historie -->
            <div class="admin-section">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-clock-history text-info"></i> Export-Historie
                </h3>
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
                                            <div class="small">
                                                <?= htmlspecialchars($row['date_from']) ?> - 
                                                <?= htmlspecialchars($row['date_to']) ?> 
                                                <span class="badge bg-secondary"><?= strtoupper($row['format']) ?></span>
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
                            <div class="list-group-item text-muted small">Keine Export-Historie verfügbar.</div>
                        <?php endif;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
}

.admin-section h3 {
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
}

.form-label.small {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

.list-group-item {
    padding: 0.75rem 1rem;
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
}

[data-bs-theme="dark"] .list-group-item {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .list-group-item:hover {
    background-color: rgba(255, 255, 255, 0.08);
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
