<?php
// Verhindere jegliche Ausgabe
ob_start();

// Grundlegende Includes
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/auth.php';
require_once 'includes/permissions.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Session starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfe Berechtigung
if (!check_permission('export')) {
    ob_end_clean();
    header('Location: error.php?message=' . urlencode('Keine Berechtigung'));
    exit;
}

try {
    // Datumskonvertierung
    $von = isset($_GET['von']) ? DateTime::createFromFormat('d.m.Y', $_GET['von'])->format('Y-m-d') : date('Y-m-01');
    $bis = isset($_GET['bis']) ? DateTime::createFromFormat('d.m.Y', $_GET['bis'])->format('Y-m-d') : date('Y-m-t');
    $format = $_GET['format'] ?? 'xlsx';

    // Hole die Einträge mit Saldo
    $sql = "SELECT 
        ke.*,
        (SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0) 
         FROM kassenbuch_eintraege k2 
         WHERE k2.datum <= ke.datum AND k2.id <= ke.id) as saldo
        FROM kassenbuch_eintraege ke
        WHERE ke.datum BETWEEN ? AND ?
        ORDER BY ke.datum ASC, ke.id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $von, $bis);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Erstelle Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Setze Überschriften
    $headers = ['Datum', 'Beleg-Nr.', 'Bemerkung', 'Einnahme', 'Ausgabe', 'Saldo'];
    foreach (range('A', 'F') as $i => $col) {
        $sheet->setCellValue($col . '1', $headers[$i]);
        $sheet->getStyle($col . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ]
        ]);
    }

    // Fülle Daten ein
    $row = 2;
    foreach ($data as $entry) {
        $sheet->setCellValue('A' . $row, date('d.m.Y', strtotime($entry['datum'])));
        $sheet->setCellValue('B' . $row, $entry['beleg_nr']);
        $sheet->setCellValue('C' . $row, $entry['bemerkung']);
        $sheet->setCellValue('D' . $row, $entry['einnahme']);
        $sheet->setCellValue('E' . $row, $entry['ausgabe']);
        $sheet->setCellValue('F' . $row, $entry['saldo']);
        
        // Formatiere Zahlen
        $sheet->getStyle('D' . $row . ':F' . $row)->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        
        $row++;
    }

    // Summenzeile
    $row++;
    $lastDataRow = $row - 2;
    $sheet->setCellValue('C' . $row, 'Summen:');
    $sheet->setCellValue('D' . $row, '=SUM(D2:D' . $lastDataRow . ')');
    $sheet->setCellValue('E' . $row, '=SUM(E2:E' . $lastDataRow . ')');
    $sheet->setCellValue('F' . $row, '=D' . $row . '-E' . $row);
    
    // Formatiere Summenzeile
    $sheet->getStyle('C' . $row . ':F' . $row)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'CCCCCC']
        ]
    ]);
    $sheet->getStyle('D' . $row . ':F' . $row)->getNumberFormat()
        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

    // Spaltenbreite automatisch anpassen
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Lösche den Output-Buffer
    ob_end_clean();

    // Setze die korrekten Header je nach Format
    switch($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="Kassenbuch_Export_' . date('Y-m-d_His') . '.csv"');
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
            break;
            
        case 'pdf':
            require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
            
            // Hole Firmeninformationen aus den Einstellungen
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

            // Hole den aktuellen Kassenstand
            $sql = "SELECT 
                (SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0) 
                 FROM kassenbuch_eintraege 
                 WHERE datum < ?) as anfangsbestand";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $von);
            $stmt->execute();
            $result = $stmt->get_result();
            $kasseninfo = $result->fetch_assoc();
            $anfangsbestand = $kasseninfo['anfangsbestand'];

            // Erstelle PDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Füge Seite hinzu
            $pdf->AddPage();
            
            // Titel
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Kassenbuch', 0, 1, 'C');
            $pdf->Ln(2);
            
            // Firmeninfo und Details
            $pdf->SetFont('helvetica', '', 10);
            
            // Linke Spalte
            $pdf->Cell(20, 6, 'Firma:', 0, 0);
            $pdf->Cell(90, 6, $company_address, 0, 0);
            
            // Rechte Spalte
            $pdf->Cell(40, 6, 'Anfangsbestand', 0, 0);
            $pdf->Cell(30, 6, number_format($anfangsbestand, 2, ',', '.') . ' €', 0, 1, 'R');
            
            $pdf->Cell(20, 6, 'Seite:', 0, 0);
            $pdf->Cell(90, 6, '1', 0, 0);
            
            // Berechne Summen für den Kopfbereich
            $sumEinnahmen = array_sum(array_column($data, 'einnahme'));
            $pdf->Cell(40, 6, 'Einnahmen', 0, 0);
            $pdf->Cell(30, 6, number_format($sumEinnahmen, 2, ',', '.') . ' €', 0, 1, 'R');
            
            $pdf->Cell(20, 6, 'Jahr:', 0, 0);
            $pdf->Cell(90, 6, date('Y', strtotime($von)), 0, 0);
            
            $sumAusgaben = array_sum(array_column($data, 'ausgabe'));
            $pdf->Cell(40, 6, 'Ausgaben', 0, 0);
            $pdf->Cell(30, 6, number_format($sumAusgaben, 2, ',', '.') . ' €', 0, 1, 'R');
            
            $pdf->Cell(20, 6, 'Monat:', 0, 0);
            $pdf->Cell(90, 6, date('F', strtotime($von)), 0, 0);
            
            $aktuellerBestand = $anfangsbestand + $sumEinnahmen - $sumAusgaben;
            $pdf->Cell(40, 6, 'Aktueller Kassenbestand', 0, 0);
            $pdf->Cell(30, 6, number_format($aktuellerBestand, 2, ',', '.') . ' €', 0, 1, 'R');
            
            $pdf->Ln(5);
            
            // Tabellenkopf
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 200, 200);
            
            // Behalte die aktuellen Spaltenbreiten bei
            $widths = [25, 20, 65, 25, 25, 25];
            $headers = ['Datum', 'Beleg-Nr.', 'Buchungstext/Belegtext', 'Einnahmen (€)', 'Ausgaben (€)', 'Saldo'];
            
            foreach($headers as $i => $header) {
                $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Tabelleninhalt
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            
            foreach($data as $row) {
                $pdf->Cell($widths[0], 6, date('d.m.Y', strtotime($row['datum'])), 1, 0, 'L', $fill);
                $pdf->Cell($widths[1], 6, $row['beleg_nr'], 1, 0, 'L', $fill);
                $pdf->Cell($widths[2], 6, $row['bemerkung'], 1, 0, 'L', $fill);
                
                // Einnahmen in Grün
                if ($row['einnahme'] > 0) {
                    $pdf->SetTextColor(0, 120, 0);
                    $pdf->Cell($widths[3], 6, number_format($row['einnahme'], 2, ',', '.'), 1, 0, 'R', $fill);
                    $pdf->SetTextColor(0);
                } else {
                    $pdf->Cell($widths[3], 6, '', 1, 0, 'R', $fill);
                }
                
                // Ausgaben in Rot
                if ($row['ausgabe'] > 0) {
                    $pdf->SetTextColor(120, 0, 0);
                    $pdf->Cell($widths[4], 6, number_format($row['ausgabe'], 2, ',', '.'), 1, 0, 'R', $fill);
                    $pdf->SetTextColor(0);
                } else {
                    $pdf->Cell($widths[4], 6, '', 1, 0, 'R', $fill);
                }
                
                // Saldo
                $pdf->Cell($widths[5], 6, number_format($row['saldo'], 2, ',', '.'), 1, 0, 'R', $fill);
                
                $pdf->Ln();
                $fill = !$fill;
            }
            
            // Summenzeile
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(200, 200, 200);
            
            $pdf->Cell(array_sum(array_slice($widths, 0, 3)), 7, 'Summen:', 1, 0, 'R', true);
            $pdf->Cell($widths[3], 7, number_format($sumEinnahmen, 2, ',', '.'), 1, 0, 'R', true);
            $pdf->Cell($widths[4], 7, number_format($sumAusgaben, 2, ',', '.'), 1, 0, 'R', true);
            $pdf->Cell($widths[5], 7, number_format($aktuellerBestand, 2, ',', '.'), 1, 0, 'R', true);
            
            // Sende PDF zum Download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="Kassenbuch_Export_' . date('Y-m-d_His') . '.pdf"');
            $pdf->Output('D');
            exit;
            
        case 'xlsx':
        default:
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Kassenbuch_Export_' . date('Y-m-d_His') . '.xlsx"');
            $writer = new Xlsx($spreadsheet);
            break;
    }

    // Sende die Datei direkt zum Download (nur für Excel und CSV)
    if ($format !== 'pdf') {
        $writer->save('php://output');
    }
    exit;

} catch (Exception $e) {
    ob_end_clean();
    header('Location: error.php?message=' . urlencode('Fehler beim Export: ' . $e->getMessage()));
    exit;
}
  