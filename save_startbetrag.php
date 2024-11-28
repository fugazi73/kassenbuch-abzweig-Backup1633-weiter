<?php
require_once 'config.php';
require_once 'functions.php';
check_login();

// Prüfen ob User Admin ist
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode([
        'success' => false,
        'message' => 'Nur Administratoren können den Startbetrag ändern.'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Prüfen ob bereits ein Startbetrag existiert
        $startbetrag_exists = $conn->query("SELECT COUNT(*) as count FROM kassenbuch_eintraege WHERE bemerkung = 'Startbetrag'")->fetch_assoc()['count'] > 0;
        
        $datum = $_POST['datum'];
        $betrag = floatval($_POST['betrag']);
        
        if ($startbetrag_exists) {
            // Update existierenden Startbetrag
            $stmt = $conn->prepare("UPDATE kassenbuch_eintraege 
                                  SET datum = ?, einnahme = ?, kassenstand = ?
                                  WHERE bemerkung = 'Startbetrag'");
            $stmt->bind_param("sdd", $datum, $betrag, $betrag);
        } else {
            // Neuen Startbetrag einfügen
            $stmt = $conn->prepare("INSERT INTO kassenbuch_eintraege 
                                  (datum, bemerkung, einnahme, ausgabe, saldo, kassenstand)
                                  VALUES (?, 'Startbetrag', ?, 0, ?, ?)");
            $stmt->bind_param("sddd", $datum, $betrag, $betrag, $betrag);
        }
        
        if ($stmt->execute()) {
            // Kassenstand für alle nachfolgenden Einträge neu berechnen
            $conn->query("SET @running_total := $betrag;
                         UPDATE kassenbuch_eintraege 
                         SET kassenstand = (@running_total := @running_total + einnahme - ausgabe)
                         WHERE bemerkung != 'Startbetrag'
                         ORDER BY datum ASC, id ASC");
            
            echo json_encode([
                'success' => true,
                'message' => 'Startbetrag wurde ' . ($startbetrag_exists ? 'aktualisiert' : 'gespeichert')
            ]);
        }
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Fehler: ' . $e->getMessage()
        ]));
    }
} 