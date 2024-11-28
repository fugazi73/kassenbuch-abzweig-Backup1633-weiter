<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Nur Admin-Zugriff erlauben
if (!is_admin()) {
    handle_forbidden();
}

$success_message = '';
$error_message = '';

// Einstellungen aus der Datenbank laden
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Startbetrag-Informationen abrufen
$startbetrag_query = $conn->query("SELECT datum, einnahme as betrag FROM kassenbuch_eintraege WHERE bemerkung = 'Startbetrag' ORDER BY datum DESC LIMIT 1");
$startbetrag_info = $startbetrag_query->fetch_assoc();
$startbetrag = $startbetrag_info['betrag'] ?? 0;
$startbetrag_datum = $startbetrag_info['datum'] ?? date('Y-m-d');

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Logo-Upload für hellen Modus
        if (isset($_FILES['logo_light']) && $_FILES['logo_light']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = handleLogoUpload($_FILES['logo_light'], 'logo_light');
            updateSetting($conn, 'logo_light', $uploadPath);
        }
        
        // Logo-Upload für dunklen Modus
        if (isset($_FILES['logo_dark']) && $_FILES['logo_dark']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = handleLogoUpload($_FILES['logo_dark'], 'logo_dark');
            updateSetting($conn, 'logo_dark', $uploadPath);
        }
        
        // Seitenname aktualisieren
        if (isset($_POST['site_name'])) {
            updateSetting($conn, 'site_name', $_POST['site_name']);
        }
        
        // Kassenstart aktualisieren
        if (isset($_POST['cash_start'])) {
            $cashStart = str_replace(',', '.', $_POST['cash_start']);
            if (!is_numeric($cashStart)) {
                throw new Exception('Kassenstart muss eine Zahl sein.');
            }
            updateSetting($conn, 'cash_start', $cashStart);
        }
        
        $success_message = 'Einstellungen wurden erfolgreich gespeichert.';
        
        // Einstellungen neu laden
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Hilfsfunktionen
function handleLogoUpload($file, $prefix) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Nur JPEG, PNG und GIF Dateien sind erlaubt.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Die Datei ist zu groß. Maximale Größe ist 5MB.');
    }
    
    $uploadDir = __DIR__ . '/images/';
    
    // Erstelle das Verzeichnis, falls es nicht existiert
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Konnte Upload-Verzeichnis nicht erstellen.');
        }
    }
    
    // Prüfe Schreibrechte
    if (!is_writable($uploadDir)) {
        throw new Exception('Keine Schreibrechte im Upload-Verzeichnis.');
    }
    
    $fileName = $prefix . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $uploadPath = $uploadDir . $fileName;
    
    // Entferne alte Datei falls vorhanden
    $existingFiles = glob($uploadDir . $prefix . '.*');
    foreach ($existingFiles as $existingFile) {
        if (file_exists($existingFile)) {
            unlink($existingFile);
        }
    }
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Fehler beim Hochladen der Datei.');
    }
    
    // Gebe relativen Pfad zurück
    return 'images/' . $fileName;
}

function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                           VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
}

$page_title = "Einstellungen";
require_once 'includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Einstellungen</h1>
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear"></i> Administration
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="admin.php"><i class="bi bi-people"></i> Benutzerverwaltung</a></li>
                        <li><a class="dropdown-item" href="backup.php"><i class="bi bi-download"></i> Backup & Restore</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Einstellungen</a></li>
                    </ul>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Logo Upload Hell -->
                        <div class="mb-4">
                            <h5>Logo für helles Design</h5>
                            <div class="mb-2">
                                <?php if (!empty($settings['logo_light']) && file_exists($settings['logo_light'])): ?>
                                    <img src="<?= htmlspecialchars($settings['logo_light']) ?>" 
                                         alt="Logo (Hell)" 
                                         class="img-thumbnail"
                                         style="max-height: 100px; max-width: 300px;">
                                <?php else: ?>
                                    <p class="text-muted">Kein Logo vorhanden</p>
                                <?php endif; ?>
                            </div>
                            <label for="logo_light" class="form-label">Neues Logo (Hell) hochladen</label>
                            <input type="file" class="form-control" id="logo_light" name="logo_light" accept="image/*">
                        </div>

                        <!-- Logo Upload Dunkel -->
                        <div class="mb-4">
                            <h5>Logo für dunkles Design</h5>
                            <div class="mb-2">
                                <?php if (!empty($settings['logo_dark']) && file_exists($settings['logo_dark'])): ?>
                                    <img src="<?= htmlspecialchars($settings['logo_dark']) ?>" 
                                         alt="Logo (Dunkel)" 
                                         class="img-thumbnail bg-dark"
                                         style="max-height: 100px; max-width: 300px;">
                                <?php else: ?>
                                    <p class="text-muted">Kein Logo vorhanden</p>
                                <?php endif; ?>
                            </div>
                            <label for="logo_dark" class="form-label">Neues Logo (Dunkel) hochladen</label>
                            <input type="file" class="form-control" id="logo_dark" name="logo_dark" accept="image/*">
                        </div>
                        
                        <div class="form-text mb-4">Erlaubte Formate: JPEG, PNG, GIF. Maximale Größe: 5MB</div>
                        
                        <!-- Seitenname -->
                        <div class="mb-4">
                            <label for="site_name" class="form-label">Seitenname</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                   value="<?= htmlspecialchars($settings['site_name'] ?? COMPANY_NAME) ?>">
                        </div>
                        
                        <!-- Kassenstart -->
                        <div class="mb-4">
                            <h5>Kassenstart</h5>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label for="startbetrag_datum" class="form-label">Datum</label>
                                    <input type="date" class="form-control" id="startbetrag_datum" 
                                           value="<?= htmlspecialchars($startbetrag_datum) ?>" required>
                                </div>
                                <div class="col-auto">
                                    <label for="startbetrag" class="form-label">Betrag (€)</label>
                                    <input type="text" class="form-control" id="startbetrag" 
                                           value="<?= number_format($startbetrag, 2, ',', '.') ?>" required>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-primary d-block" onclick="saveStartbetrag()">
                                        Kassenstart speichern
                                    </button>
                                </div>
                            </div>
                            <div id="startbetrag_message" class="mt-2"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5"></div>

<script>
function saveStartbetrag() {
    const datum = document.getElementById('startbetrag_datum').value;
    const betrag = document.getElementById('startbetrag').value.replace(',', '.');
    const messageDiv = document.getElementById('startbetrag_message');
    
    fetch('save_startbetrag.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `datum=${encodeURIComponent(datum)}&betrag=${encodeURIComponent(betrag)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger">Ein Fehler ist aufgetreten.</div>';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 

<!-- Bootstrap Bundle mit Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 