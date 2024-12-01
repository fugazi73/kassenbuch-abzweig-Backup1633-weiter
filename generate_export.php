<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'includes/init.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

if (!is_admin()) {
    handle_forbidden();
}

try {
    // Datumskonvertierung von deutschem Format (DD.MM.YYYY) zu MySQL Format (YYYY-MM-DD)
    $von = isset($_GET['von']) ? DateTime::createFromFormat('d.m.Y', $_GET['von'])->format('Y-m-d') : '';
    $bis = isset($_GET['bis']) ? DateTime::createFromFormat('d.m.Y', $_GET['bis'])->format('Y-m-d') : '';
    $format = $_GET['format'] ?? 'xlsx';

    // Prüfe ob das Exportverzeichnis existiert
    if (!file_exists('exports')) {
        mkdir('exports', 0777, true);
    }

    // Generiere eindeutigen Dateinamen
    $filename = 'Kassenbuch_Export_' . date('Y-m-d_His') . '.' . $format;
    $filepath = 'exports/' . $filename;

    // Hole den aktuellen Kassenstand und Startbetrag
    $sql = "SELECT 
        (SELECT COALESCE(einnahme, 0) 
         FROM kassenbuch_eintraege 
         WHERE bemerkung = 'Kassenstart' 
         ORDER BY datum DESC, id DESC 
         LIMIT 1) as startbetrag,
        (SELECT COALESCE(SUM(einnahme), 0) - COALESCE(SUM(ausgabe), 0) 
         FROM kassenbuch_eintraege) as gesamt_kassenstand";

    $result = $conn->query($sql);
    $kasseninfo = $result->fetch_assoc();
    $startbetrag = $kasseninfo['startbetrag'];
    $current_kassenstand = $kasseninfo['gesamt_kassenstand'];

    // Hole die Einträge mit Saldo
    $sql = "WITH RECURSIVE running_balance AS (
        SELECT 
            datum,
            einnahme,
            ausgabe,
            bemerkung,
            id,
            @running_total := ? as initial_balance,
            (@running_total := @running_total + COALESCE(einnahme, 0) - COALESCE(ausgabe, 0)) as saldo
        FROM 
            kassenbuch_eintraege,
            (SELECT @running_total := ?) r
        WHERE 
            datum BETWEEN ? AND ?
            AND bemerkung != 'Kassenstart'
        ORDER BY 
            datum ASC, id ASC
    )
    SELECT * FROM running_balance";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddss", $current_kassenstand, $current_kassenstand, $von, $bis);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Hole die Firmeninformationen aus den Einstellungen
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
    } else {
        $company_address = 'Mustermann GmbH, Musterstrasse 4, 8280 Kreuzlingen';
    }

    // Excel erstellen
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Seiteneinstellungen
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    
    // Spaltenbreiten
    $sheet->getColumnDimension('A')->setWidth(10);   // Beleg-Nr.
    $sheet->getColumnDimension('B')->setWidth(15);   // Datum
    $sheet->getColumnDimension('C')->setWidth(50);   // Buchungstext
    $sheet->getColumnDimension('D')->setWidth(20);   // Einnahmen
    $sheet->getColumnDimension('E')->setWidth(20);   // Ausgaben
    
    // Titel
    $sheet->mergeCells('A1:E1');
    $sheet->setCellValue('A1', 'Kassenbuch');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']]
    ]);
    
    // Firmeninfo
    $sheet->setCellValue('A2', 'Firma');
    $sheet->setCellValue('B2', $company_address);
    $sheet->mergeCells('B2:E2');
    $sheet->getStyle('A2')->getFont()->setBold(true);
    
    // Linke Spalte: Seite, Jahr, Monat
    $sheet->setCellValue('A3', 'Seite');
    $sheet->setCellValue('B3', '1');
    $sheet->getStyle('B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    $sheet->setCellValue('A4', 'Jahr');
    $sheet->setCellValue('B4', date('Y', strtotime($von)));
    $sheet->getStyle('B4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    $sheet->setCellValue('A5', 'Monat');
    $sheet->setCellValue('B5', date('F', strtotime($von)));
    $sheet->getStyle('B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    // Rechte Spalte: Kassenbestand
    $sheet->setCellValue('D3', 'Anfangsbestand');
    $sheet->setCellValue('E3', $current_kassenstand);
    $sheet->getStyle('E3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    $sheet->setCellValue('D4', 'Einnahmen');
    $sheet->setCellValue('E4', '=SUM(D9:D33)');
    $sheet->getStyle('E4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    $sheet->setCellValue('D5', 'Ausgaben');
    $sheet->setCellValue('E5', '=SUM(E9:E33)');
    $sheet->getStyle('E5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    $sheet->setCellValue('D6', 'Aktueller Kassenbestand');
    $sheet->setCellValue('E6', '=E3+E4-E5');
    $sheet->getStyle('E6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
    
    // Formatierung der Beschriftungen
    $sheet->getStyle('A3:A5')->getFont()->setBold(true);
    $sheet->getStyle('D3:D6')->getFont()->setBold(true);
    
    // Tabellenkopf
    $sheet->setCellValue('A8', 'Beleg-Nr.');
    $sheet->setCellValue('B8', 'Datum');
    $sheet->setCellValue('C8', 'Buchungstext/Belegtext');
    $sheet->setCellValue('D8', 'Einnahmen (€)');
    $sheet->setCellValue('E8', 'Ausgaben (€)');
    
    // Formatierung Tabellenkopf
    $sheet->getStyle('A8:E8')->applyFromArray([
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]
    ]);
    
    // Daten einfügen
    $row = 9;
    $lastRow = $row + 24; // 25 Zeilen
    
    // Leere Zeilen vorbereiten
    for ($i = $row; $i <= $lastRow; $i++) {
        $sheet->setCellValue("A$i", $i-8);
        // Rahmen für jede Zelle
        $sheet->getStyle("A$i:E$i")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        // Hellgrauer Hintergrund für gerade Zeilen
        if (($i-8) % 2 == 0) {
            $sheet->getStyle("A$i:E$i")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
        }
    }
    
    // Echte Daten einfügen
    foreach ($data as $entry) {
        if ($row > $lastRow) break;
        
        $date = DateTime::createFromFormat('Y-m-d', $entry['datum'])->format('d.m.Y');
        
        $sheet->setCellValue("A$row", $row-8);
        $sheet->setCellValue("B$row", $date);
        $sheet->setCellValue("C$row", $entry['bemerkung']);
        
        // Einnahmen in Grün
        if ($entry['einnahme'] > 0) {
            $sheet->setCellValue("D$row", $entry['einnahme']);
            $sheet->getStyle("D$row")->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
            $sheet->getStyle("D$row")->getFont()->setBold(true);
        }

        // Ausgaben in Rot
        if ($entry['ausgabe'] > 0) {
            $sheet->setCellValue("E$row", $entry['ausgabe']);
            $sheet->getStyle("E$row")->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
            $sheet->getStyle("E$row")->getFont()->setBold(true);
        }
        
        $row++;
    }
    
    // Summenzeile
    $sumRow = $lastRow + 1;
    $sheet->mergeCells("A$sumRow:C$sumRow");
    $sheet->setCellValue("A$sumRow", 'Summe');
    $sheet->setCellValue("D$sumRow", "=SUM(D9:D$lastRow)");
    $sheet->setCellValue("E$sumRow", "=SUM(E9:E$lastRow)");
    
    // Saldo
    $saldoRow = $sumRow + 1;
    $sheet->mergeCells("A$saldoRow:D$saldoRow");
    $sheet->setCellValue("A$saldoRow", 'Saldo');
    $sheet->setCellValue("E$saldoRow", "=D$sumRow-E$sumRow");
    
    // Formatiere Summen und Saldo
    $sheet->getStyle("D$sumRow")->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
    $sheet->getStyle("E$sumRow")->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
    if ($sheet->getCell("E$saldoRow")->getCalculatedValue() >= 0) {
        $sheet->getStyle("E$saldoRow")->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
    } else {
        $sheet->getStyle("E$saldoRow")->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
    }
    
    // Formatierung
    // Währungsformat mit genau 2 Dezimalstellen
    $numberFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2;
    $sheet->getStyle('E3:E6')->getNumberFormat()->setFormatCode($numberFormat . ' €');
    $sheet->getStyle("D9:E$lastRow")->getNumberFormat()->setFormatCode($numberFormat . ' €');
    $sheet->getStyle("D$sumRow:E$saldoRow")->getNumberFormat()->setFormatCode($numberFormat . ' €');
    
    // Runde alle Zahlen auf 2 Dezimalstellen
    foreach($data as $entry) {
        if ($entry['einnahme'] > 0) {
            $entry['einnahme'] = round($entry['einnahme'], 2);
        }
        if ($entry['ausgabe'] > 0) {
            $entry['ausgabe'] = round($entry['ausgabe'], 2);
        }
    }
    
    // Ausrichtung
    $sheet->getStyle('A8:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D8:E'.$saldoRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Rahmen für Summe und Saldo
    $sheet->getStyle("A$sumRow:E$saldoRow")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]
    ]);
    
    // Formatierung der Spalten
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getStyle('C:D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
    $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

    // Farbliche Formatierung für Einnahmen/Ausgaben/Kassenstand
    foreach ($sheet->getRowIterator(9, $lastRow) as $row) {
        $rowIndex = $row->getRowIndex();
        
        // Einnahmen grün
        $einnahme = $sheet->getCell('D'.$rowIndex)->getValue();
        if ($einnahme > 0) {
            $sheet->getStyle('D'.$rowIndex)->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
            $sheet->getStyle('D'.$rowIndex)->getFont()->setBold(true);
        }
        
        // Ausgaben rot
        $ausgabe = $sheet->getCell('E'.$rowIndex)->getValue();
        if ($ausgabe > 0) {
            $sheet->getStyle('E'.$rowIndex)->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
            $sheet->getStyle('E'.$rowIndex)->getFont()->setBold(true);
        }
    }
    
    // Speichere Datei je nach Format
    switch($format) {
        case 'xlsx':
            $writer = new Xlsx($spreadsheet);
            // Speichere die Datei physisch für die Historie
            $writer->save($filepath);
            
            // Sende die Datei zum Download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            readfile($filepath);
            break;
            
        case 'csv':
            $writer = new Csv($spreadsheet);
            // Speichere die Datei physisch für die Historie
            $writer->save($filepath);
            
            // Sende die Datei zum Download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            readfile($filepath);
            break;
            
        case 'pdf':
            // PDF Export mit TCPDF
            require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
            
            // Stelle sicher, dass das Exportverzeichnis existiert
            if (!is_dir('exports')) {
                mkdir('exports', 0777, true);
            }
            
            // Erstelle den absoluten Pfad für die PDF-Datei
            $pdf_file = __DIR__ . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . basename($filename);
            
            // Erstelle PDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            
            // Entferne Standard-Header/Footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Setze Dokumenteigenschaften
            $pdf->SetCreator('Kassenbuch System');
            $pdf->SetAuthor('System');
            $pdf->SetTitle('Kassenbuch Export');
            
            // Füge Seite hinzu
            $pdf->AddPage();
            
            // Setze Schriftart
            $pdf->SetFont('helvetica', '', 11);
            
            // Titel
            $pdf->SetFillColor(211, 211, 211);
            $pdf->Cell(0, 10, 'Kassenbuch', 1, 1, 'C', true);
            
            // Firmeninfo
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Firma:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $company_address, 0, 1);
            
            // Linke Spalte
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Seite:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(60, 8, '1', 0, 0);
            
            // Rechte Spalte
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 8, 'Anfangsbestand:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, number_format($current_kassenstand, 2, ',', '.') . ' €', 0, 1);
            
            // Zweite Zeile
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Jahr:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(60, 8, date('Y', strtotime($von)), 0, 0);
            
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 8, 'Einnahmen:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $sumEinnahmen = array_sum(array_column($data, 'einnahme'));
            $pdf->Cell(0, 8, number_format($sumEinnahmen, 2, ',', '.') . ' €', 0, 1);
            
            // Dritte Zeile
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(30, 8, 'Monat:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(60, 8, date('F', strtotime($von)), 0, 0);
            
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 8, 'Ausgaben:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $sumAusgaben = array_sum(array_column($data, 'ausgabe'));
            $pdf->Cell(0, 8, number_format($sumAusgaben, 2, ',', '.') . ' €', 0, 1);
            
            // Vierte Zeile (nur rechts)
            $pdf->Cell(90, 8, '', 0, 0);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(50, 8, 'Aktueller Kassenbestand:', 0, 0);
            $pdf->SetFont('helvetica', '', 11);
            $aktuellerBestand = $current_kassenstand + $sumEinnahmen - $sumAusgaben;
            $pdf->Cell(0, 8, number_format($aktuellerBestand, 2, ',', '.') . ' €', 0, 1);
            
            // Abstand vor Tabelle
            $pdf->Ln(5);
            
            // Tabellenkopf
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 11);
            $header = array('Beleg-Nr.', 'Datum', 'Buchungstext/Belegtext', 'Einnahmen (€)', 'Ausgaben (€)');
            $w = array(20, 25, 75, 35, 35);
            
            foreach($header as $i => $h) {
                $pdf->Cell($w[$i], 8, $h, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Tabelleninhalt
            $pdf->SetFont('helvetica', '', 11);
            $fill = false;
            $counter = 1;
            
            // Fülle 25 Zeilen
            for($i = 1; $i <= 25; $i++) {
                $rowData = array_shift($data);
                
                if($rowData) {
                    $date = DateTime::createFromFormat('Y-m-d', $rowData['datum'])->format('d.m.Y');
                    $einnahme = $rowData['einnahme'] > 0 ? number_format($rowData['einnahme'], 2, ',', '.') : '';
                    $ausgabe = $rowData['ausgabe'] > 0 ? number_format($rowData['ausgabe'], 2, ',', '.') : '';
                    
                    $pdf->Cell($w[0], 8, $counter, 1, 0, 'C', $fill);
                    $pdf->Cell($w[1], 8, $date, 1, 0, 'L', $fill);
                    $pdf->Cell($w[2], 8, $rowData['bemerkung'], 1, 0, 'L', $fill);
                    
                    // Einnahmen in Grün
                    if ($rowData['einnahme'] > 0) {
                        $pdf->SetTextColor(0, 128, 0);
                    }
                    $pdf->Cell($w[3], 8, $einnahme, 1, 0, 'R', $fill);
                    $pdf->SetTextColor(0, 0, 0);
                    
                    // Ausgaben in Rot
                    if ($rowData['ausgabe'] > 0) {
                        $pdf->SetTextColor(128, 0, 0);
                    }
                    $pdf->Cell($w[4], 8, $ausgabe, 1, 0, 'R', $fill);
                    $pdf->SetTextColor(0, 0, 0);
                } else {
                    $pdf->Cell($w[0], 8, $counter, 1, 0, 'C', $fill);
                    $pdf->Cell($w[1], 8, '', 1, 0, 'L', $fill);
                    $pdf->Cell($w[2], 8, '', 1, 0, 'L', $fill);
                    $pdf->Cell($w[3], 8, '', 1, 0, 'R', $fill);
                    $pdf->Cell($w[4], 8, '', 1, 0, 'R', $fill);
                }
                
                $pdf->Ln();
                $counter++;
                $fill = !$fill;
            }
            
            // Summenzeile
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(array_sum(array_slice($w, 0, 3)), 8, 'Summe', 1, 0, 'L');
            $pdf->Cell($w[3], 8, number_format($sumEinnahmen, 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell($w[4], 8, number_format($sumAusgaben, 2, ',', '.'), 1, 1, 'R');
            
            // Saldozeile
            $saldo = $sumEinnahmen - $sumAusgaben;
            $pdf->Cell(array_sum(array_slice($w, 0, 4)), 8, 'Saldo', 1, 0, 'L');
            $pdf->SetTextColor(0, 128, 0); // Grün für positiven Saldo
            $pdf->Cell($w[4], 8, number_format($saldo, 2, ',', '.'), 1, 1, 'R');
            
            // Speichere die PDF physisch für die Historie
            $pdf->Output($pdf_file, 'F');
            
            // Sende die Datei zum Download
            $pdf->Output($filename, 'D');
            break;
            
        default:
            throw new Exception('Ungültiges Export-Format');
    }

    // Speichere in Historie
    $sql = "INSERT INTO export_history (date_from, date_to, format, filename, created_by) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", 
        $von,
        $bis,
        $format,
        $filename,
        $_SESSION['username']
    );
    $stmt->execute();

} catch (Exception $e) {
    // Fehlerbehandlung
    header('HTTP/1.1 500 Internal Server Error');
    echo "Fehler beim Export: " . $e->getMessage();
    exit;
}
  