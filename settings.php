<?php
require_once 'includes/init.php';
require_once 'functions.php';

// Prüfe Berechtigung
if (!is_chef() && !is_admin()) {
    header('Location: error.php?message=Keine Berechtigung');
    exit;
}

// Hole aktuelle Einstellungen aus der Datenbank
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Hole das letzte Update-Datum des Kassenstarts
$datum_query = $conn->query("
    SELECT DATE_FORMAT(updated_at, '%d.%m.%Y') as datum 
    FROM settings 
    WHERE setting_key = 'kassenstart' 
    LIMIT 1
");
$datum_info = $datum_query->fetch_assoc();
$kassenstart_datum = $datum_info['datum'] ?? date('d.m.Y');
$kassenstart_betrag = $settings['kassenstart'] ?? '0';

// Header einbinden
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title mb-4">
                        <i class="bi bi-gear-fill text-primary"></i> Einstellungen
                    </h1>

                    <!-- Kasseneinstellungen -->
                    <div class="settings-section mb-5">
                        <h3 class="border-bottom pb-2 mb-4">
                            <i class="bi bi-cash text-success"></i> Kasseneinstellungen
                        </h3>
                        <div class="settings-content">
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">Kassenstart</h5>
                                <div class="alert alert-info shadow-sm">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Aktueller Startbetrag: <strong><?= number_format(floatval($kassenstart_betrag), 2, ',', '.') ?> €</strong> 
                                    <br>
                                    <small class="text-muted">Stand: <?= $kassenstart_datum ?></small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="kassenstart_datum" class="form-label">Startdatum</label>
                                        <input type="date" class="form-control" id="kassenstart_datum" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="kassenstart" class="form-label">Startbetrag</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="kassenstart" 
                                                   value="<?= $kassenstart_betrag ?>" 
                                                   step="0.01" min="0" required>
                                            <span class="input-group-text">€</span>
                                            <button class="btn btn-primary" id="saveKassenstartBtn">
                                                <i class="bi bi-save"></i> Speichern
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Dieser Betrag wird als Startbetrag im Kassenbuch verwendet.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logo Einstellungen -->
                    <div class="settings-section mb-5">
                        <h3 class="border-bottom pb-2 mb-4">
                            <i class="bi bi-image text-info"></i> Logo Einstellungen
                        </h3>
                        <div class="settings-content">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Logo (Hell)</h5>
                                            <div class="logo-preview mb-3 text-center p-3 bg-light rounded">
                                                <img src="<?= $settings['logo_light'] ?? 'images/logo_light.png' ?>" 
                                                     alt="Logo (Hell)" 
                                                     id="logoLightPreview"
                                                     class="img-fluid"
                                                     style="max-height: 100px; width: auto;">
                                            </div>
                                            <div class="logo-upload text-center">
                                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('logoLightInput').click()">
                                                    <i class="bi bi-upload"></i> Datei auswählen
                                                </button>
                                                <div class="mt-2 text-muted small file-name">Keine ausgewählt</div>
                                                <input type="file" class="d-none" id="logoLightInput" accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">Logo (Dunkel)</h5>
                                            <div class="logo-preview mb-3 text-center p-3 bg-dark rounded">
                                                <img src="<?= $settings['logo_dark'] ?? 'images/logo_dark.png' ?>" 
                                                     alt="Logo (Dunkel)" 
                                                     id="logoDarkPreview"
                                                     class="img-fluid"
                                                     style="max-height: 100px; width: auto;">
                                            </div>
                                            <div class="logo-upload text-center">
                                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('logoDarkInput').click()">
                                                    <i class="bi bi-upload"></i> Datei auswählen
                                                </button>
                                                <div class="mt-2 text-muted small file-name">Keine ausgewählt</div>
                                                <input type="file" class="d-none" id="logoDarkInput" accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-primary" onclick="saveLogo()">
                                    <i class="bi bi-save"></i> Logos speichern
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Weitere Einstellungen -->
                    <div class="settings-section">
                        <h3 class="border-bottom pb-2 mb-4">
                            <i class="bi bi-sliders text-warning"></i> Weitere Einstellungen
                        </h3>
                        <div class="settings-content">
                            <form id="siteSettingsForm" class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="siteName" class="form-label">Seitenname</label>
                                        <input type="text" class="form-control" id="siteName" name="site_name" 
                                               value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Dieser Name wird im Browser-Tab und der Navigation angezeigt.
                                        </div>
                                    </div>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kassenstart Speichern
    const saveButton = document.getElementById('saveKassenstartBtn');
    if (saveButton) {
        saveButton.addEventListener('click', async function() {
            const betrag = document.getElementById('kassenstart').value;
            const datum = document.getElementById('kassenstart_datum').value;

            if (!betrag || !datum) {
                alert('Bitte geben Sie einen Betrag und ein Datum ein.');
                return;
            }

            try {
                const response = await fetch('save_kassenstart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        betrag: parseFloat(betrag),
                        datum: datum
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Kassenstart wurde erfolgreich gespeichert.');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Fehler beim Speichern des Kassenstarts');
                }
            } catch (error) {
                console.error('Fehler:', error);
                alert('Fehler beim Speichern: ' + error.message);
            }
        });
    }

    // Logo-Upload Vorschau
    function updateLogoPreview(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            const fileNameDiv = input.parentElement.querySelector('.file-name');
            if (fileNameDiv) {
                fileNameDiv.textContent = file.name;
            }
        }
    }

    // Logo-Upload Event-Listener
    document.getElementById('logoLightInput').addEventListener('change', function() {
        updateLogoPreview(this, 'logoLightPreview');
    });

    document.getElementById('logoDarkInput').addEventListener('change', function() {
        updateLogoPreview(this, 'logoDarkPreview');
    });

    // Logo speichern
    window.saveLogo = async function() {
        try {
            const formData = new FormData();
            const lightInput = document.getElementById('logoLightInput');
            const darkInput = document.getElementById('logoDarkInput');
            
            if (lightInput.files[0]) {
                formData.append('logo_light', lightInput.files[0]);
            }
            
            if (darkInput.files[0]) {
                formData.append('logo_dark', darkInput.files[0]);
            }

            if (!lightInput.files[0] && !darkInput.files[0]) {
                alert('Bitte wählen Sie mindestens ein Logo aus.');
                return;
            }

            const response = await fetch('save_logo.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                throw new Error(result.message || 'Fehler beim Speichern der Logos');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Speichern der Logos: ' + error.message);
        }
    };

    // Seitenname speichern
    document.getElementById('siteSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const siteName = document.getElementById('siteName').value;
        
        try {
            const response = await fetch('save_site_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    site_name: siteName
                })
            });

            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                throw new Error(result.message || 'Fehler beim Speichern der Einstellungen');
            }
        } catch (error) {
            console.error('Fehler:', error);
            alert('Fehler beim Speichern der Einstellungen: ' + error.message);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 