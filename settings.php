<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!is_admin()) {
    handle_forbidden();
}

// Einstellungen aus der Datenbank laden
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Grundeinstellungen
$site_name = $settings['site_name'] ?? 'Kassenbuch';
$page_title = "Einstellungen - " . htmlspecialchars($site_name);

// Startbetrag-Informationen abrufen
$startbetrag_query = $conn->query("SELECT datum, einnahme as betrag FROM kassenbuch_eintraege WHERE bemerkung = 'Kassenstart' ORDER BY datum DESC LIMIT 1");
$startbetrag_info = $startbetrag_query->fetch_assoc();

// Header einbinden
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <h2 class="settings-title mb-4">
                <i class="bi bi-gear"></i> Einstellungen
            </h2>

            <!-- Kasseneinstellungen -->
            <div class="settings-section mb-4">
                <h5 class="settings-header">
                    <i class="bi bi-cash"></i> Kasseneinstellungen
                </h5>
                <div class="settings-content">
                    <div class="kassenstart-section">
                        <h6>Kassenstart</h6>
                        
                        <?php if ($startbetrag_info): ?>
                        <div class="startbetrag-info">
                            Aktueller Startbetrag: <?= number_format($startbetrag_info['betrag'], 2, ',', '.') ?> € (<?= date('d.m.Y', strtotime($startbetrag_info['datum'])) ?>)
                        </div>
                        <?php endif; ?>
                        
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Datum</label>
                                <input type="date" class="form-control" id="startdatum" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Betrag (€)</label>
                                <input type="number" step="0.01" class="form-control" id="startbetrag" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" onclick="saveKassenstart()">
                                <i class="bi bi-save"></i> Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo Einstellungen -->
            <div class="settings-section mb-4">
                <h5 class="settings-header">
                    <i class="bi bi-image"></i> Logo Einstellungen
                </h5>
                <div class="settings-content">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Logo (Hell)</h6>
                            <div class="logo-preview mb-3">
                                <img src="<?= htmlspecialchars($settings['logo_light'] ?? 'images/logo_light.png') ?>" 
                                     alt="Logo (Hell)" 
                                     id="logoLightPreview"
                                     class="img-fluid">
                            </div>
                            <div class="logo-upload">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('logoLightInput').click()">
                                    Datei auswählen
                                </button>
                                <span class="ms-2 text-muted">Keine ausgewählt</span>
                                <input type="file" class="d-none" id="logoLightInput" name="logo_light" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Logo (Dunkel)</h6>
                            <div class="logo-preview mb-3">
                                <img src="<?= htmlspecialchars($settings['logo_dark'] ?? 'images/logo_dark.png') ?>" 
                                     alt="Logo (Dunkel)" 
                                     id="logoDarkPreview"
                                     class="img-fluid">
                            </div>
                            <div class="logo-upload">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('logoDarkInput').click()">
                                    Datei auswählen
                                </button>
                                <span class="ms-2 text-muted">Keine ausgewählt</span>
                                <input type="file" class="d-none" id="logoDarkInput" name="logo_dark" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" onclick="saveLogo()">
                            <i class="bi bi-save"></i> Logos speichern
                        </button>
                    </div>
                </div>
            </div>

            <!-- Weitere Einstellungen -->
            <div class="settings-section">
                <h5 class="settings-header">
                    <i class="bi bi-gear"></i> Weitere Einstellungen
                </h5>
                <div class="settings-content">
                    <form id="siteSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Seitenname</label>
                                    <input type="text" class="form-control" id="siteName" name="site_name" 
                                           value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-title {
    color: var(--text-color);
    font-size: 1.5rem;
    font-weight: 500;
}

.settings-section {
    background: var(--surface-card);
    border-radius: 0.5rem;
    overflow: hidden;
}

.settings-header {
    background: var(--surface-section);
    padding: 1rem;
    margin: 0;
    border-bottom: 1px solid var(--border-color);
    font-size: 1rem;
    font-weight: 500;
}

.settings-content {
    padding: 1.5rem;
}

.kassenstart-section h6 {
    margin-bottom: 1rem;
    color: var(--text-color);
}

.startbetrag-info {
    background: var(--primary-dark);
    color: var(--text-color);
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}

.form-control {
    background: var(--surface-hover);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    height: 40px;
}

.form-control:focus {
    background: var(--surface-hover);
    border-color: var(--primary-color);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
}

.btn-primary {
    background: var(--primary-color);
    border: none;
    padding: 0.5rem 1rem;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.logo-preview {
    background: var(--surface-hover);
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-preview img {
    max-width: 200px;
    max-height: 200px;
    width: auto;
    height: auto;
}

.btn-secondary {
    background: var(--surface-hover);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-secondary:hover {
    background: var(--surface-hover-darker);
    border-color: var(--border-color-darker);
}
</style>

<script>
function saveLogo() {
    const formData = new FormData();
    const lightInput = document.getElementById('logoLightInput');
    const darkInput = document.getElementById('logoDarkInput');
    
    if (lightInput.files[0]) {
        formData.append('logo_light', lightInput.files[0]);
    }
    if (darkInput.files[0]) {
        formData.append('logo_dark', darkInput.files[0]);
    }
    
    fetch('save_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Logos wurden erfolgreich gespeichert');
            window.location.reload();
        } else {
            alert('Fehler: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}

function saveKassenstart() {
    const startdatum = document.getElementById('startdatum').value;
    const startbetrag = document.getElementById('startbetrag').value;
    
    if (!startdatum || !startbetrag) {
        alert('Bitte füllen Sie alle Felder aus.');
        return;
    }
    
    const formData = new FormData();
    formData.append('datum', startdatum);
    formData.append('betrag', startbetrag);
    
    fetch('save_kassenstart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Kassenstart wurde erfolgreich gespeichert');
            window.location.reload();
        } else {
            alert('Fehler: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    });
}

// Dateinamen anzeigen
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'Keine ausgewählt';
        e.target.parentElement.querySelector('span').textContent = fileName;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 