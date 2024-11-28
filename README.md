# Kassenbuch Web-Anwendung

## Überblick
Eine umfassende webbasierte Kassenbuch-Verwaltung, entwickelt mit PHP, MySQL und Bootstrap. Die Anwendung ermöglicht die Verwaltung von Kassentransaktionen (Einnahmen und Ausgaben), bietet Benutzerrollenverwaltung, Exportfunktionen und vieles mehr, um die Buchführung zu vereinfachen.

## Funktionen
- **Benutzerauthentifizierung und rollenbasierte Zugriffssteuerung** (Admin, Chef)
- **Kassentransaktionsverwaltung** (Einnahmen/Ausgaben)
- **Hell-/Dunkel-Design** zur Auswahl
- **Exportfunktionen**: Excel, PDF, CSV, HTML
- **Backup- und Wiederherstellungsfunktionen**
- **Benutzerverwaltung** für Administratoren
- **Startbetrag-Verwaltung**
- **Excel-Import-Funktion**
- **Detaillierte Statistiken und Berichte**

## Systemvoraussetzungen
- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- Composer
- Webserver (Apache oder Nginx)

## Installation

1. **Repository klonen**
   ```bash
   git clone [REPOSITORY_URL]
   ```

2. **Abhängigkeiten installieren**
   ```bash
   composer install
   ```

3. **Datenbank erstellen und Schema importieren**
   
   Erstellen Sie eine MySQL-Datenbank und importieren Sie das Schema:
   ```bash
   mysql -u root -p kassenbuch_db < database/schema.sql
   ```

4. **Datenbank-Verbindung konfigurieren**
   
   Bearbeiten Sie die Datei `config.php` und geben Sie Ihre Datenbank-Zugangsdaten ein:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'kassenbuch_db');
   define('DB_USER', 'benutzername');
   define('DB_PASSWORD', 'passwort');
   ?>
   ```

5. **Berechtigungen setzen**
   
   Setzen Sie die erforderlichen Berechtigungen für die Verzeichnisse `backups/` und `uploads/`:
   ```bash
   chmod -R 775 backups/
   chmod -R 775 uploads/
   ```

## Verwendung
1. **Zugriff auf die Anwendung**: 
   
   Öffnen Sie die Anwendung in Ihrem Webbrowser:
   ```
   http://localhost/kassenbuch
   ```

2. **Anmeldung**:
   
   Melden Sie sich mit Ihren Zugangsdaten an, um auf die Funktionen zuzugreifen.

3. **Navigation**:
   
   Nutzen Sie die Navigationsleiste, um zwischen verschiedenen Funktionen zu wechseln:
   - **Kassenbuch**: Verwaltung von Einnahmen und Ausgaben
   - **Administration**: Benutzerverwaltung
   - **Exportfunktionen**: Datenexport in verschiedene Formate
   - **Backup/Wiederherstellung**: Sichern und Wiederherstellen von Daten
   - **Statistiken**: Detaillierte Berichte und Analysen

## Sicherheit
- **Passwort-Hashing**: Zur Sicherung von Benutzerdaten.
- **Rollenbasierte Zugriffssteuerung**: Zugriffsrechte werden je nach Rolle (Admin, Chef) verwaltet.
- **Eingabevalidierung und -bereinigung**: Schutz vor schädlichen Eingaben.
- **Session-Management**: Sicherer Zugriff auf Benutzerkonten.
- **SQL-Injection-Prävention**: Schutz durch vorbereitete SQL-Anweisungen.

## Wartung
- **Automatische Backups**: Implementiert durch das Skript `cron_backup_cleanup.php`.
- **Bereinigung alter Backups**: Automatische Löschung nach 30 Tagen.
- **Fehlerprotokollierung**: Fehler werden in `error.log` aufgezeichnet.

## Mitwirken
Wir freuen uns über Beiträge! Bitte lesen Sie `CONTRIBUTING.md` für weitere Details zu unserem Verhaltenskodex und dem Prozess zum Einreichen von Pull Requests.

## Lizenz
Dieses Projekt ist unter der [entsprechenden Lizenz] lizenziert. Weitere Details finden Sie in der Datei `LICENSE.md`.

## Autoren
- Patrick Bednarz / meincode.eu 2024

## Danksagungen
- **Bootstrap**: Für das CSS-Framework
- **PhpSpreadsheet**: Für die Tabellenbearbeitung
- **TCPDF**: Für die PDF-Erstellung
- **SimpleXLSX**: Für das Arbeiten mit Excel-Dateien

Für weitere Informationen lesen Sie bitte die detaillierte Dokumentation in den jeweiligen Komponenten-Dateien und deren Kommentare.
