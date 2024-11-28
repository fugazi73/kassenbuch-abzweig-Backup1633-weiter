<?php
require_once 'config.php';
check_login();

// Nur Chef oder Admin dürfen Statistiken sehen
if (!is_chef_or_admin()) {
    die(json_encode(['success' => false, 'message' => 'Keine Berechtigung']));
}

$stat = $_GET['stat'] ?? '';
$today = date('Y-m-d');
$month = date('Y-m');

try {
    switch($stat) {
        case 'eintraege_heute':
            $sql = "SELECT COUNT(*) as value FROM kassenbuch_eintraege WHERE DATE(datum) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $value = $result['value'] . ' Einträge';
            break;
            
        case 'umsatz_heute':
            $sql = "SELECT SUM(einnahme) as einnahmen, SUM(ausgabe) as ausgaben 
                   FROM kassenbuch_eintraege WHERE DATE(datum) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $umsatz = ($result['einnahmen'] ?? 0) + ($result['ausgaben'] ?? 0);
            $value = number_format(abs($umsatz), 2, ',', '.') . ' €';
            break;
            
        case 'eintraege_monat':
            $sql = "SELECT COUNT(*) as value FROM kassenbuch_eintraege 
                   WHERE DATE_FORMAT(datum, '%Y-%m') = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $month);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $value = $result['value'] . ' Einträge';
            break;
            
        case 'umsatz_monat':
            $sql = "SELECT SUM(einnahme) as einnahmen, SUM(ausgabe) as ausgaben 
                   FROM kassenbuch_eintraege 
                   WHERE DATE_FORMAT(datum, '%Y-%m') = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $month);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $umsatz = ($result['einnahmen'] ?? 0) + ($result['ausgaben'] ?? 0);
            $value = number_format(abs($umsatz), 2, ',', '.') . ' €';
            break;
            
        default:
            throw new Exception('Ungültige Statistik ausgewählt');
    }
    
    echo json_encode([
        'success' => true,
        'value' => $value
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 