<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

$von = filter_var($_GET['von'] ?? date('Y-m-01'), FILTER_SANITIZE_STRING);
$bis = filter_var($_GET['bis'] ?? date('Y-m-t'), FILTER_SANITIZE_STRING);
$format = in_array($_GET['format'] ?? 'xlsx', ['xlsx', 'pdf']) ? $_GET['format'] : 'xlsx';

// Daten aus der Datenbank holen
$sql = "SELECT datum, beleg_nr, bemerkung, einnahme, ausgabe, saldo, kassenstand 
        FROM kassenbuch_eintraege 
        WHERE datum BETWEEN ? AND ? 
        ORDER BY datum ASC, id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $von, $bis);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

try {
    require_once 'vendor/autoload.php';
    switch($format) {
        case 'xlsx':
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $sheet->setCellValue('A1', 'Datum');
            $sheet->setCellValue('B1', 'Beleg');
            $sheet->setCellValue('C1', 'Bemerkung');
            $sheet->setCellValue('D1', 'Einnahme');
            $sheet->setCellValue('E1', 'Ausgabe');
            $sheet->setCellValue('F1', 'Saldo');
            
            // Daten
            $row = 2;
            foreach ($data as $entry) {
                $sheet->setCellValue('A'.$row, $entry['datum']);
                $sheet->setCellValue('B'.$row, $entry['beleg_nr']);
                $sheet->setCellValue('C'.$row, $entry['bemerkung']);
                $sheet->setCellValue('D'.$row, $entry['einnahme']);
                $sheet->setCellValue('E'.$row, $entry['ausgabe']);
                $sheet->setCellValue('F'.$row, $entry['saldo']);
                $row++;
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Kassenbuch_Export.xlsx"');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            break;
            
        case 'pdf':
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('Kassenbuch');
            $pdf->SetTitle('Kassenbuch Export');
            
            $pdf->AddPage('L'); // Querformat
            
            // Tabelle erstellen
            $html = '<table border="1" cellpadding="4">
                        <tr>
                            <th>Datum</th>
                            <th>Beleg</th>
                            <th>Bemerkung</th>
                            <th>Einnahme</th>
                            <th>Ausgabe</th>
                            <th>Saldo</th>
                        </tr>';
                        
            foreach ($data as $entry) {
                $html .= '<tr>
                            <td>'.date('d.m.Y', strtotime($entry['datum'])).'</td>
                            <td>'.$entry['beleg_nr'].'</td>
                            <td>'.$entry['bemerkung'].'</td>
                            <td>'.number_format($entry['einnahme'], 2, ',', '.').' €</td>
                            <td>'.number_format($entry['ausgabe'], 2, ',', '.').' €</td>
                            <td>'.number_format($entry['saldo'], 2, ',', '.').' €</td>
                        </tr>';
            }
            $html .= '</table>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
            
            $pdf->Output('Kassenbuch_Export.pdf', 'D');
            break;
    }
} catch (Exception $e) {
    die(json_encode(['error' => 'Export-Bibliotheken nicht gefunden. Bitte "composer install" ausführen.']));
}

exit; 