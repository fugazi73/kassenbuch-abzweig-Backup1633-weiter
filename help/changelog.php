<?php
// Definiere den Basis-Pfad
$root_dir = dirname(__DIR__);

// Lade die erforderlichen Dateien
require_once $root_dir . '/includes/init.php';
require_once $root_dir . '/functions.php';

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
require_once $root_dir . '/includes/header.php';

// Changelog-Daten
$changelog = [
    '1.0.3' => [
        'date' => '2024-01-29',
        'changes' => [
            'Hinzugefügt' => [
                'Verbesserte Benutzerprofilseite',
                'Passwort-Änderungsfunktion',
                'Erweiterte Berechtigungsverwaltung'
            ],
            'Verbessert' => [
                'Optimierte Theme-Umschaltung ohne Flackern',
                'Konsistentes Design über alle Seiten',
                'Verbesserte Filter-Darstellung',
                'Einheitliche Header- und Footer-Gestaltung'
            ],
            'Behoben' => [
                'Theme-Switching Probleme behoben',
                'Berechtigungsprüfungen optimiert',
                'Filter-Anzeige bei deaktivierten Berechtigungen korrigiert'
            ],
            'Optimiert' => [
                'Code-Struktur überarbeitet',
                'Performance-Optimierungen',
                'Verbesserte Benutzerführung'
            ]
        ]
    ],
    '1.0.2' => [
        'date' => '2023-12-29',
        'changes' => [
            'Hinzugefügt' => [
                'Excel-Import in der Hauptnavigation',
                'Neues einheitliches Design für alle Admin-Bereiche',
                'Dark Mode Unterstützung für alle Komponenten'
            ],
            'Verbessert' => [
                'Optimierte Navigation mit konsistenter Breite',
                'Verbesserte Darstellung der Karten und Tabellen',
                'Einheitliche Schriftgrößen und Abstände',
                'Bessere Lesbarkeit im Dark Mode'
            ],
            'Behoben' => [
                'Fehler bei der Kassenstart-Berechnung',
                'Probleme mit der Backup-Funktionalität',
                'Layout-Probleme in verschiedenen Ansichten',
                'Pfad-Probleme im Changelog-Bereich'
            ],
            'Optimiert' => [
                'Performance der Datenbank-Abfragen',
                'Speichernutzung bei Backup-Operationen',
                'Ladezeiten durch optimierte Ressourcen'
            ]
        ]
    ],
    '1.0.1' => [
        'date' => '2024-11-29',
        'changes' => [
            'Hinzugefügt' => [
                'Initiale Version des Kassenbuchs',
                'Grundlegende Einstellungsmöglichkeiten',
                'Standard-Spalten für Kassenbucheinträge',
                'Logo-Upload für helles und dunkles Design',
                'Benutzerdefinierte Spalten mit verschiedenen Datentypen',
                'Excel-Mapping für alle Spalten'
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
    ],
    '1.0.0' => [
        'date' => '2024-11-25',
        'changes' => [
            'Hinzugefügt' => [
                'Erste Beta-Version',
                'Grundlegende Kassenbuch-Funktionen',
                'Einfache Benutzerverwaltung'
            ]
        ]
    ]
];
?>

<!-- Hauptinhalt -->
<main class="changelog-page">
    <div class="max-width-container py-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 card-title mb-4">
                    <i class="bi bi-clock-history text-primary"></i> Changelog
                </h1>

                <?php foreach ($changelog as $version => $info): ?>
                    <div class="admin-section mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h3 class="mb-0">
                                <i class="bi bi-tag text-success"></i> Version <?= htmlspecialchars($version) ?>
                            </h3>
                            <span class="badge bg-secondary small">
                                <?= date('d.m.Y', strtotime($info['date'])) ?>
                            </span>
                        </div>
                        
                        <?php foreach ($info['changes'] as $category => $changes): ?>
                            <div class="mb-3">
                                <h4 class="small fw-bold mb-2">
                                    <?php
                                    $icon = 'bi-info-circle text-primary'; // Default
                                    switch($category) {
                                        case 'Hinzugefügt':
                                            $icon = 'bi-plus-circle text-success';
                                            break;
                                        case 'Entfernt':
                                            $icon = 'bi-dash-circle text-danger';
                                            break;
                                        case 'Geändert':
                                            $icon = 'bi-arrow-repeat text-warning';
                                            break;
                                        case 'Verbessert':
                                            $icon = 'bi-stars text-info';
                                            break;
                                        case 'Behoben':
                                            $icon = 'bi-bug text-danger';
                                            break;
                                        case 'Implementiert':
                                            $icon = 'bi-check-circle text-success';
                                            break;
                                        case 'Optimiert':
                                            $icon = 'bi-gear text-info';
                                            break;
                                    }
                                    ?>
                                    <i class="bi <?= $icon ?>"></i> 
                                    <?= htmlspecialchars($category) ?>
                                </h4>
                                <ul class="list-unstyled mb-0 ps-3">
                                    <?php foreach ($changes as $change): ?>
                                        <li class="mb-1 small text-muted">
                                            <i class="bi bi-dot"></i>
                                            <?= htmlspecialchars($change) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<style>
.card-title {
    font-size: 1.1rem;
    font-weight: 500;
}

.admin-section h3 {
    font-size: 0.95rem;
    font-weight: 500;
}

.admin-section h4 {
    font-size: 0.9rem;
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
}

.breadcrumb {
    font-size: 0.85rem;
}

.breadcrumb-item a {
    text-decoration: none;
}

.list-unstyled li {
    line-height: 1.5;
}

[data-bs-theme="dark"] .card {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-bs-theme="dark"] .text-muted {
    color: rgba(255, 255, 255, 0.65) !important;
}

.max-width-container {
    max-width: 1320px;
    margin: 0 auto;
    padding-left: 1rem;
    padding-right: 1rem;
}

.bi {
    font-size: 1rem;
}
</style>

<?php require_once($root_dir . '/includes/footer.php'); ?> 