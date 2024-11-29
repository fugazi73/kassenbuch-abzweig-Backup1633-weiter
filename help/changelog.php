<?php
// Definiere den Basis-Pfad
$root_dir = dirname(__DIR__);

// Zuerst die Funktionen laden
require_once($root_dir . '/includes/functions.php');

// Dann die Konfiguration und Authentifizierung
require_once($root_dir . '/config.php');
require_once($root_dir . '/includes/auth.php');

// Prüfen ob die Funktionen verfügbar sind
if (!function_exists('getSetting')) {
    die('Error: Required functions are not available');
}

// Hole die Einstellungen
try {
    $site_name = getSetting($conn, 'site_name', 'Kassenbuch');
    $logo_light = getSetting($conn, 'logo_light', 'images/logo_light.png');
    $logo_dark = getSetting($conn, 'logo_dark', 'images/logo_dark.png');
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Seitentitel setzen
$page_title = "Changelog - $site_name";
$current_page = 'changelog';

// Header einbinden
require_once($root_dir . '/includes/header.php');

// Changelog-Daten
$changelog = [
    '1.0' => [
        'date' => '2024-01-29',
        'changes' => [
            'Hinzugefügt' => [
                'Initiale Version des Kassenbuchs',
                'Grundlegende Einstellungsmöglichkeiten',
                'Standard-Spalten für Kassenbucheinträge',
                'Logo-Upload für helles und dunkles Design',
                'Benutzerdefinierte Spalten mit verschiedenen Datentypen',
                'Excel-Mapping für alle Spalten',
            ],
            'Implementiert' => [
                'Basis-Funktionalität für Kassenbuchführung',
                'Benutzerauthentifizierung und -verwaltung',
                'Einstellungen für Kassenstart und Kassensturz',
                'Responsives Design und Layout',
                'Dark/Light Mode Unterstützung'
            ],
            'Optimiert' => [
                'Performance der Datenbankanfragen',
                'Benutzerfreundliche Oberfläche',
                'Datenbankstruktur für flexible Anpassungen'
            ]
        ]
    ]
];
?>

<!-- Hauptinhalt -->
<main class="changelog-page">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Changelog</h1>
                <p class="lead mb-0">Übersicht aller Änderungen und Verbesserungen</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../index.php">Start</a></li>
                    <li class="breadcrumb-item"><a href="../help.php">Hilfe</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Changelog</li>
                </ol>
            </nav>
        </div>

        <?php foreach ($changelog as $version => $info): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">
                        Version <?= htmlspecialchars($version) ?>
                    </h2>
                    <span class="badge bg-secondary">
                        <?= htmlspecialchars($info['date']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php foreach ($info['changes'] as $category => $changes): ?>
                        <div class="mb-3">
                            <h3 class="h6 mb-2">
                                <?php
                                $icon = match($category) {
                                    'Hinzugefügt' => 'bi-plus-circle',
                                    'Entfernt' => 'bi-dash-circle',
                                    'Geändert' => 'bi-arrow-repeat',
                                    'Verbessert' => 'bi-stars',
                                    'Behoben' => 'bi-bug',
                                    'Implementiert' => 'bi-check-circle',
                                    default => 'bi-info-circle'
                                };
                                ?>
                                <i class="bi <?= $icon ?>"></i> 
                                <?= htmlspecialchars($category) ?>
                            </h3>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($changes as $change): ?>
                                    <li class="mb-1">
                                        <i class="bi bi-dot"></i>
                                        <?= htmlspecialchars($change) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php
// Footer einbinden
require_once($root_dir . '/includes/footer.php');
?> 