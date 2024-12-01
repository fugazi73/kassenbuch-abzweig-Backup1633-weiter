<?php
session_start();
require_once 'includes/init.php';
require_once 'functions.php';

// Prüfe Berechtigung
if (!is_chef() && !is_admin()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Keine Berechtigung']));
}

// Hole und validiere die Daten
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['betrag']) || !isset($data['datum'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Betrag und Datum fehlen']));
}

$betrag = floatval($data['betrag']);
$datum = $data['datum'];

if ($betrag < 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Betrag muss positiv sein']));
}

try {
    // Starte Transaktion
    $conn->begin_transaction();

    // 1. Speichere in den Einstellungen
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value, updated_at) 
        VALUES ('kassenstart', ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            setting_value = ?,
            updated_at = NOW()
    ");
    $betragStr = number_format($betrag, 2, '.', '');
    $stmt->bind_param("ss", $betragStr, $betragStr);
    $stmt->execute();

    // 2. Lösche alten Kassenstart-Eintrag
    $stmt = $conn->prepare("DELETE FROM kassenbuch_eintraege WHERE bemerkung = 'Kassenstart'");
    $stmt->execute();

    // 3. Füge neuen Kassenstart ein
    $stmt = $conn->prepare("
        INSERT INTO kassenbuch_eintraege 
        (datum, bemerkung, einnahme, ausgabe, kassenstand) 
        VALUES (?, 'Kassenstart', ?, 0, ?)
    ");
    $stmt->bind_param("sdd", $datum, $betrag, $betrag);
    $stmt->execute();

    // 4. Aktualisiere alle Kassenstände
    $sql = "
        UPDATE kassenbuch_eintraege 
        SET kassenstand = (
            SELECT running_total
            FROM (
                SELECT 
                    id,
                    (
                        SELECT SUM(CASE 
                            WHEN bemerkung = 'Kassenstart' THEN einnahme
                            ELSE einnahme - ausgabe 
                        END)
                        FROM kassenbuch_eintraege AS t2
                        WHERE t2.datum <= t1.datum
                        AND (t2.datum < t1.datum OR t2.id <= t1.id)
                    ) as running_total
                FROM kassenbuch_eintraege AS t1
            ) AS subquery
            WHERE subquery.id = kassenbuch_eintraege.id
        )
        WHERE bemerkung != 'Kassenstart'
        ORDER BY datum ASC, id ASC
    ";
    $conn->query($sql);

    // Commit Transaktion
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Kassenstart wurde erfolgreich gespeichert']);
} catch (Exception $e) {
    // Rollback bei Fehler
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
} 