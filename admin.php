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
                    <div class="col-6">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
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
                    <div class="col-6">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
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

            <!-- Schnellzugriff -->
            <div class="admin-section mb-4">
                <h3 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-lightning text-warning"></i> Schnellzugriff
                </h3>
                <div class="d-flex justify-content-between gap-3">
                    <a href="admin_users.php" class="card flex-fill text-decoration-none hover-shadow">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3">
                            <i class="bi bi-people text-primary mb-2"></i>
                            <h5 class="card-title mb-1">Benutzerverwaltung</h5>
                            <div class="text-muted small">Benutzer verwalten</div>
                        </div>
                    </a>
                    <a href="backup.php" class="card flex-fill text-decoration-none hover-shadow">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3">
                            <i class="bi bi-download text-success mb-2"></i>
                            <h5 class="card-title mb-1">Backup & Restore</h5>
                            <div class="text-muted small">Daten sichern</div>
                        </div>
                    </a>
                    <a href="settings.php" class="card flex-fill text-decoration-none hover-shadow">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3">
                            <i class="bi bi-gear text-info mb-2"></i>
                            <h5 class="card-title mb-1">Einstellungen</h5>
                            <div class="text-muted small">System anpassen</div>
                        </div>
                    </a>
                    <a href="import_excel.php" class="card flex-fill text-decoration-none hover-shadow">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3">
                            <i class="bi bi-file-earmark-excel text-success mb-2"></i>
                            <h5 class="card-title mb-1">Excel Import</h5>
                            <div class="text-muted small">Daten importieren</div>
                        </div>
                    </a>
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
                    <div class="col-4">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-people text-primary me-3"></i>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Benutzer</h5>
                                    <div class="text-muted small">Aktive Konten</div>
                                </div>
                                <div class="h4 mb-0 ms-3"><?= $user_count ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-journal-text text-success me-3"></i>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">Einträge</h5>
                                    <div class="text-muted small">Gesamt</div>
                                </div>
                                <div class="h4 mb-0 ms-3"><?= $entry_count ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
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

<style>
.hover-shadow:hover {
    box-shadow: 0 .25rem .5rem rgba(0,0,0,.1)!important;
    transform: translateY(-1px);
    transition: all .2s ease-in-out;
}

[data-bs-theme="dark"] .card {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .hover-shadow:hover {
    background-color: rgba(255, 255, 255, 0.08);
}

[data-bs-theme="dark"] .text-muted {
    color: rgba(255, 255, 255, 0.65) !important;
}

.card {
    border: 1px solid rgba(0,0,0,.125);
    min-width: 0;
}

.card-title {
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
    white-space: nowrap;
}

.admin-section h3 {
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 0.75rem 0;
}

.bi {
    font-size: 1.5rem;
}

.small {
    font-size: 0.8rem;
}

.gap-3 {
    gap: 1rem !important;
}

.py-3 {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.flex-fill {
    flex: 1 1 0;
    min-width: 0;
}
</style>

<?php include 'includes/footer.php'; ?> 
