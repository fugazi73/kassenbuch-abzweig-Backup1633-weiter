<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

// Prüfe ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

// Hole den aktuellen Pfad für die Navigation
$current_page = 'manual';

// Header einbinden
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Handbuch-spezifische Styles -->
<link href="../assets/css/manual.css" rel="stylesheet">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Kassenbuch - Benutzerhandbuch</h1>
        <span class="text-muted">Version 1.0</span>
    </div>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Dieses Handbuch wird regelmäßig aktualisiert. Neue Funktionen sind mit <span class="feature-new">NEU</span> gekennzeichnet.
    </div>
    
    <div class="accordion mt-4" id="manualAccordion">
        <!-- Allgemeine Funktionen -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#allgemein">
                    Allgemeine Funktionen (Alle Benutzer)
                </button>
            </h2>
            <div id="allgemein" class="accordion-collapse collapse show" data-bs-parent="#manualAccordion">
                <div class="accordion-body">
                    <div class="manual-section">
                        <h5>Kassenbuch Ansicht</h5>
                        <ul>
                            <li>Anzeige aller Kassenbucheinträge mit Datum, Bemerkung, Einnahmen, Ausgaben und Kassenstand</li>
                            <li>Positive Beträge werden in Grün, negative in Rot angezeigt</li>
                            <li>25 Einträge pro Seite mit Seitennavigation</li>
                            <li>Automatische Berechnung des laufenden Kassenstands</li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Filterung und Suche</h5>
                        <ul>
                            <li>Nach Monat filtern (Format: MM.YYYY)</li>
                            <li>Nach Zeitraum filtern (Von-Bis Datum)</li>
                            <li>Nach Typ filtern (Einnahmen/Ausgaben)</li>
                            <li>Nach Bemerkungen filtern mit Autovervollständigung <span class="feature-new">NEU</span></li>
                            <li>Aktive Filter werden angezeigt und können zurückgesetzt werden</li>
                            <li>Kombinierte Filterung möglich</li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Darstellung</h5>
                        <ul>
                            <li>Übersichtliche Tabellenansicht</li>
                            <li>Farbliche Kennzeichnung der Beträge</li>
                            <li>Responsive Design für alle Bildschirmgrößen</li>
                            <li>Dark Mode verfügbar <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chef Funktionen -->
        <?php if (in_array($_SESSION['user_role'], ['chef', 'admin'])): ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#chef">
                    Chef-Funktionen
                </button>
            </h2>
            <div id="chef" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body">
                    <div class="manual-section">
                        <h5>Einträge bearbeiten</h5>
                        <ul>
                            <li>Bestehende Einträge können bearbeitet werden</li>
                            <li>Datum, Bemerkung, Einnahmen und Ausgaben können geändert werden</li>
                            <li>Änderungen werden im System protokolliert</li>
                            <li>Direkte Bearbeitung in der Tabelle möglich <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Export-Funktionen</h5>
                        <ul>
                            <li>Export als Excel (XLSX), CSV oder PDF</li>
                            <li>Zeitraum für den Export wählbar</li>
                            <li>Exportierte Dateien enthalten farbliche Kennzeichnung</li>
                            <li>Export-Historie einsehbar</li>
                            <li>Automatische Formatierung der Zahlen</li>
                            <li>Firmeninformationen werden automatisch eingefügt <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Auswertungen</h5>
                        <ul>
                            <li>Tagesabschluss erstellen</li>
                            <li>Monatsübersicht anzeigen</li>
                            <li>Jahresübersicht verfügbar</li>
                            <li>Kassenstand-Entwicklung einsehen</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Funktionen -->
        <?php if (in_array($_SESSION['user_role'], ['admin'])): ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#admin">
                    Administrator-Funktionen
                </button>
            </h2>
            <div id="admin" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body">
                    <div class="manual-section">
                        <h5>Benutzerverwaltung</h5>
                        <ul>
                            <li>Neue Benutzer anlegen</li>
                            <li>Benutzerrollen zuweisen (Admin, Chef, Benutzer)</li>
                            <li>Benutzer deaktivieren/aktivieren</li>
                            <li>Passwörter zurücksetzen</li>
                            <li>Benutzeraktivitäten protokollieren <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Einträge verwalten</h5>
                        <ul>
                            <li>Neue Einträge erstellen</li>
                            <li>Bestehende Einträge bearbeiten</li>
                            <li>Einträge löschen (einzeln oder mehrere)</li>
                            <li>Kassenstart festlegen und ändern</li>
                            <li>Massenbearbeitung möglich <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>System-Einstellungen</h5>
                        <ul>
                            <li>Firmeninformationen verwalten</li>
                            <li>Export-Einstellungen anpassen</li>
                            <li>System-Protokolle einsehen</li>
                            <li>Backup erstellen und wiederherstellen</li>
                            <li>Dark Mode Einstellungen <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Bemerkungen verwalten</h5>
                        <ul>
                            <li>Vordefinierte Bemerkungen erstellen und verwalten</li>
                            <li>Neue Bemerkungen direkt beim Erstellen eines Eintrags hinzufügen</li>
                            <li>Nicht mehr benötigte Bemerkungen entfernen</li>
                            <li>Bemerkungen kategorisieren <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tipps & Tricks -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipps">
                    Tipps & Tricks
                </button>
            </h2>
            <div id="tipps" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body">
                    <div class="manual-section">
                        <h5>Effizientes Arbeiten</h5>
                        <ul>
                            <li>Nutzen Sie die Filteroptionen für bessere Übersicht</li>
                            <li>Verwenden Sie vordefinierte Bemerkungen für häufige Einträge</li>
                            <li>Exportieren Sie regelmäßig Sicherungskopien</li>
                            <li>Überprüfen Sie den Kassenstand regelmäßig</li>
                            <li>Nutzen Sie Tastaturkürzel für schnelle Navigation <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Fehlervermeidung</h5>
                        <ul>
                            <li>Prüfen Sie Einträge vor dem Speichern</li>
                            <li>Achten Sie auf das korrekte Datum</li>
                            <li>Verwenden Sie Punkte statt Kommas bei Beträgen</li>
                            <li>Machen Sie regelmäßige Kassenbuch-Abschlüsse</li>
                            <li>Nutzen Sie die automatische Validierung <span class="feature-new">NEU</span></li>
                        </ul>
                    </div>

                    <div class="manual-section">
                        <h5>Tastaturkürzel <span class="feature-new">NEU</span></h5>
                        <p class="text-muted mb-2">Die folgenden Tastaturkürzel sind verfügbar:</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tastenkombination</th>
                                        <th>Funktion</th>
                                        <th>Verfügbar in</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><kbd>Strg</kbd> + <kbd>F</kbd></td>
                                        <td>Suche/Filter öffnen</td>
                                        <td>Kassenbuch</td>
                                    </tr>
                                    <tr>
                                        <td><kbd>Strg</kbd> + <kbd>E</kbd></td>
                                        <td>Export öffnen</td>
                                        <td>Kassenbuch</td>
                                    </tr>
                                    <tr>
                                        <td><kbd>Esc</kbd></td>
                                        <td>Dialog schließen</td>
                                        <td>Überall</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Tastaturkürzel funktionieren nicht, wenn Sie sich in einem Eingabefeld befinden (außer <kbd>Esc</kbd>).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tastaturkürzel-Script einbinden -->
<script src="../assets/js/shortcuts.js"></script>

<!-- Footer einbinden -->
<?php
require_once __DIR__ . '/../includes/footer.php';
?> 