<?php
session_start();
require_once 'includes/init.php';
require_once 'config.php';
require_once 'functions.php';

if (!is_admin()) {
    handle_forbidden();
}

$page_title = 'Administration Dashboard | Kassenbuch';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row my-4">
        <!-- Übersichtskarten -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-3">
                        <i class="bi bi-people"></i> Aktive Benutzer
                    </h6>
                    <h2 class="card-title display-6 mb-0" id="active_users">-</h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-3">
                        <i class="bi bi-clock-history"></i> Letzte Aktivität
                    </h6>
                    <p class="mb-0 fs-5" id="last_activity">-</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-3">
                        <i class="bi bi-hdd"></i> Letztes Backup
                    </h6>
                    <p class="mb-0 fs-5" id="last_backup">-</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-3">
                        <i class="bi bi-graph-up"></i> System Status
                    </h6>
                    <p class="mb-0 fs-5 text-success">
                        <i class="bi bi-check-circle"></i> System läuft normal
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Schnellzugriff -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Schnellzugriff</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <a href="benutzerverwaltung.php" class="btn btn-outline-primary w-100 btn-lg">
                                <i class="bi bi-people"></i> Benutzerverwaltung
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="backup.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-download"></i> Backup & Restore
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="settings.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-gear"></i> Einstellungen
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" onclick="checkSystem()">
                                <i class="bi bi-arrow-repeat"></i> System Check
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Seiten-Verwaltung</h5>
        </div>
        <div class="card-body">
            <form id="pagesForm" onsubmit="updatePages(event)">
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showImpressum" name="show_impressum">
                        <label class="form-check-label" for="showImpressum">Impressum anzeigen</label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Impressum Inhalt</label>
                    <textarea class="form-control form-control-lg" id="impressumContent" name="impressum_content" rows="10"></textarea>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showDatenschutz" name="show_datenschutz">
                        <label class="form-check-label" for="showDatenschutz">Datenschutz anzeigen</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Datenschutz Inhalt</label>
                    <textarea class="form-control" id="datenschutzContent" name="datenschutz_content" rows="10"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Speichern
                </button>
            </form>
        </div>
    </div>
</div>


<!-- JavaScript für Admin Dashboard -->
<script src="js/admin.js"></script>

<?php require_once 'includes/footer.php'; ?> 
