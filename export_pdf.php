<?php
// Verhindere jegliche Ausgabe
ob_start();

// Grundlegende Includes
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/auth.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Prüfe Berechtigung
if (!check_permission('export')) {
    ob_end_clean();
    header('Location: error.php?message=' . urlencode('Keine Berechtigung'));
    exit;
}

try {
    // Hole die Daten aus der Datenbank
    $von = $_GET['von'] ?? date('Y-m-01');
    $bis = $_GET['bis'] ?? date('Y-m-t');

    // Hole die Einträge
    $stmt = $conn->prepare("SELECT *, 
        (SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0) 
         FROM kassenbuch_eintraege k2 
         WHERE k2.datum <= k1.datum AND k2.id <= k1.id) as saldo 
        FROM kassenbuch_eintraege k1 
        WHERE datum BETWEEN ? AND ? 
        ORDER BY datum ASC, id ASC");
    $stmt->bind_param("ss", $von, $bis);
    $stmt->execute();
    $result = $stmt->get_result();
    $entries = $result->fetch_all(MYSQLI_ASSOC);

    // Hole die Firmeninformationen
    $company_info = [];
    $company_settings = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('company_name', 'company_street', 'company_zip', 'company_city')");
    while ($row = $company_settings->fetch_assoc()) {
        $company_info[$row['setting_key']] = $row['setting_value'];
    }

    // Erstelle die Firmenadresse
    $company_address = '';
    if (!empty($company_info['company_name'])) {
        $company_address .= $company_info['company_name'];
        if (!empty($company_info['company_street'])) {
            $company_address .= ', ' . $company_info['company_street'];
        }
        if (!empty($company_info['company_zip']) || !empty($company_info['company_city'])) {
            $company_address .= ', ' . $company_info['company_zip'] . ' ' . $company_info['company_city'];
        }
    }

    // Berechne Summen
    $sumEinnahmen = array_sum(array_column($entries, 'einnahme'));
    $sumAusgaben = array_sum(array_column($entries, 'ausgabe'));
    $saldo = $sumEinnahmen - $sumAusgaben;

    // Lösche den Output-Buffer
    ob_end_clean();

    // Erstelle PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Entferne Standard-Header/Footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Setze Dokumenteninformationen
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Kassenbuch System');
    $pdf->SetTitle('Kassenbuch Export');

    // Setze Ränder
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Füge eine Seite hinzu
    $pdf->AddPage();

    // Titel
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Kassenbuch', 0, 1, 'C');
    $pdf->Ln(5);

    // Firmeninfo
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 8, 'Firma:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, $company_address, 0, 1);

    // Zeitraum
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 8, 'Zeitraum:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, date('d.m.Y', strtotime($von)) . ' - ' . date('d.m.Y', strtotime($bis)), 0, 1);
    $pdf->Ln(5);

    // Tabellenkopf
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 8, 'Datum', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Beleg', 1, 0, 'C', true);
    $pdf->Cell(60, 8, 'Bemerkung', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Einnahme', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Ausgabe', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Saldo', 1, 1, 'C', true);

    // Tabelleninhalt
    $pdf->SetFont('helvetica', '', 10);
    foreach ($entries as $entry) {
        $pdf->Cell(25, 8, date('d.m.Y', strtotime($entry['datum'])), 1, 0, 'C');
        $pdf->Cell(20, 8, $entry['beleg_nr'], 1, 0, 'C');
        $pdf->Cell(60, 8, $entry['bemerkung'], 1, 0, 'L');
        $pdf->Cell(30, 8, number_format($entry['einnahme'], 2, ',', '.') . ' €', 1, 0, 'R');
        $pdf->Cell(30, 8, number_format($entry['ausgabe'], 2, ',', '.') . ' €', 1, 0, 'R');
        $pdf->Cell(30, 8, number_format($entry['saldo'], 2, ',', '.') . ' €', 1, 1, 'R');
    }

    // Summenzeile
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(105, 8, 'Summen:', 1, 0, 'R', true);
    $pdf->Cell(30, 8, number_format($sumEinnahmen, 2, ',', '.') . ' €', 1, 0, 'R', true);
    $pdf->Cell(30, 8, number_format($sumAusgaben, 2, ',', '.') . ' €', 1, 0, 'R', true);
    $pdf->Cell(30, 8, number_format($saldo, 2, ',', '.') . ' €', 1, 1, 'R', true);

    // Sende PDF zum Download
    $pdf->Output('Kassenbuch_Export_' . date('Y-m-d_His') . '.pdf', 'D');
    exit;

} catch (Exception $e) {
    ob_end_clean();
    header('Location: error.php?message=' . urlencode('Fehler beim Export: ' . $e->getMessage()));
    exit;
} 