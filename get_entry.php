<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Keine ID angegeben']);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM kassenbuch_eintraege WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'entry' => [
            'id' => $row['id'],
            'datum' => $row['datum'],
            'beleg_nr' => $row['beleg_nr'],
            'bemerkung' => $row['bemerkung'],
            'einnahme' => $row['einnahme'],
            'ausgabe' => $row['ausgabe']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Eintrag nicht gefunden']);
}

$stmt->close(); 