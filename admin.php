<?php
require_once 'includes/init.php';
require_once 'functions.php';

// Prüfe Berechtigung
if (!is_admin()) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

$page_title = "Administration - " . htmlspecialchars($site_name ?? '');
include 'includes/header.php';
?>

<div class="max-width-container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 card-title mb-4">
                <i class="bi bi-gear-fill text-primary"></i> Administration
            </h1>

            <!-- System-Status -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-info-circle text-info"></i> System-Status
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-database text-primary me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-1">Datenbank</h5>
                                        <div class="text-muted small">
                                            Version: <?= htmlspecialchars($conn->server_info) ?><br>
                                            Zeichensatz: <?= htmlspecialchars($conn->character_set_name()) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-server text-success me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-1">Server</h5>
                                        <div class="text-muted small">
                                            PHP Version: <?= phpversion() ?><br>
                                            Server: <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schnellzugriff -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-lightning text-warning"></i> Schnellzugriff
                </h3>
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="admin_users.php" class="card text-decoration-none h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <i class="bi bi-people text-primary mb-2"></i>
                                    <h5 class="card-title mb-1">Benutzerverwaltung</h5>
                                    <div class="text-muted small">Benutzer verwalten</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="backup.php" class="card text-decoration-none h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <i class="bi bi-download text-success mb-2"></i>
                                    <h5 class="card-title mb-1">Backup & Restore</h5>
                                    <div class="text-muted small">Daten sichern</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="settings.php" class="card text-decoration-none h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <i class="bi bi-gear text-info mb-2"></i>
                                    <h5 class="card-title mb-1">Einstellungen</h5>
                                    <div class="text-muted small">System anpassen</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="import_excel.php" class="card text-decoration-none h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <i class="bi bi-file-earmark-excel text-success mb-2"></i>
                                    <h5 class="card-title mb-1">Excel Import</h5>
                                    <div class="text-muted small">Daten importieren</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistiken -->
            <div class="admin-section">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-graph-up text-success"></i> Statistiken
                </h3>
                <div class="row g-3">
                    <?php
                    // Anzahl der Benutzer
                    try {
                        $user_count = $conn->query("SELECT COUNT(*) as count FROM benutzer")->fetch_assoc()['count'] ?? 0;
                    } catch (Exception $e) {
                        $user_count = 0;
                    }
                    
                    // Anzahl der Kassenbuch-Einträge
                    try {
                        $entry_count = $conn->query("SELECT COUNT(*) as count FROM kassenbuch_eintraege")->fetch_assoc()['count'] ?? 0;
                    } catch (Exception $e) {
                        $entry_count = 0;
                    }
                    
                    // Letzter Login
                    try {
                        $last_login = $conn->query("SELECT MAX(last_login) as last FROM benutzer")->fetch_assoc()['last'];
                    } catch (Exception $e) {
                        $last_login = null;
                    }
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-people text-primary me-3"></i>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">Benutzer</h5>
                                        <div class="text-muted small">Aktive Konten</div>
                                    </div>
                                    <div class="h4 mb-0 ms-3"><?= $user_count ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-journal-text text-success me-3"></i>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">Einträge</h5>
                                        <div class="text-muted small">Gesamt</div>
                                    </div>
                                    <div class="h4 mb-0 ms-3"><?= $entry_count ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock-history text-info me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-1">Letzter Login</h5>
                                        <div class="text-muted small"><?= $last_login ? date('d.m.Y H:i', strtotime($last_login)) : 'Nie' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

.table th {
    font-size: 0.85rem;
    font-weight: 500;
}

.table td {
    font-size: 0.9rem;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

.form-label.small {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.modal-title {
    font-size: 1rem;
}

[data-bs-theme="dark"] .table-light {
    background-color: rgba(255, 255, 255, 0.05);
}

[data-bs-theme="dark"] .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

[data-bs-theme="dark"] .card {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}
</style>

<?php include 'includes/footer.php'; ?> 
