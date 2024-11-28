<?php
require_once 'config.php';
check_login();
if (!is_admin()) {
    handle_forbidden();
}

// Standardwerte für Datum setzen
$von = $_GET['von'] ?? date('Y-m-01'); // Erster Tag des aktuellen Monats
$bis = $_GET['bis'] ?? date('Y-m-t');  // Letzter Tag des aktuellen Monats

function generateExcel($data, $filename, $kassenstand) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Überschrift und Datum
    $sheet->setCellValue('A1', 'Kassenbuch - Einnahmen und Ausgaben');
    $sheet->mergeCells('A1:G1');
    
    // Aktuelles Datum und Monat/Jahr der Auswertung
    $currentDate = date('d.m.Y');
    $month = date('m');
    $year = date('Y');
    $monthName = [
        '01' => 'Januar', '02' => 'Februar', '03' => 'März', '04' => 'April',
        '05' => 'Mai', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember'
    ][$month];
    
    $sheet->setCellValue('A2', "Auswertung für $monthName $year");
    $sheet->mergeCells('A2:C2');
    $sheet->setCellValue('D2', "Erstellt am: $currentDate");
    $sheet->mergeCells('D2:G2');
    
    // Kassenstand
    $sheet->setCellValue('A3', 'Kassenstand zu Beginn:');
    $sheet->mergeCells('A3:C3');
    $sheet->setCellValue('D3', $kassenstand);
    $sheet->mergeCells('D3:G3');
    
    // Styling für Überschriften
    $sheet->getStyle('A1:G3')->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    $sheet->getStyle('A1')->getFont()->setSize(14);
    $sheet->getStyle('D3')->getNumberFormat()->setFormatCode('#,##0.00 €');
    
    // Spaltenüberschriften
    $headers = ['Datum', 'Beleg-Nr.', 'Bemerkung', 'Einnahme', 'Ausgabe', 'Saldo', 'Kassenstand'];
    $sheet->fromArray($headers, NULL, 'A5');
    
    // Daten einfügen
    $sheet->fromArray($data, NULL, 'A6');
    
    // Daten-Styling
    $sheet->getStyle('A2:G'.$lastRow)->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ]);
    
    // Datum-Format
    $sheet->getStyle('A2:A'.$lastRow)->getNumberFormat()
          ->setFormatCode('YYYY-MM-DD');
    
    // Währungs-Format
    $sheet->getStyle('D2:G'.$lastRow)->getNumberFormat()
          ->setFormatCode('#,##0.00');
    
    // Spaltenbreiten anpassen
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(12);
    
    // Bedingte Formatierung für negative Werte in rot
    $conditionalStyles = $sheet->getStyle('D2:G'.$lastRow)->getConditionalStyles();
    $conditionalStyles[] = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
    $conditionalStyles[0]->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
                         ->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN)
                         ->addCondition('0');
    $conditionalStyles[0]->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
    $sheet->getStyle('D2:G'.$lastRow)->setConditionalStyles($conditionalStyles);
    
    // Excel-Datei erstellen und herunterladen
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export | Kassenbuch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="bi bi-download"></i> 
                            Daten exportieren
                        </h3>
                        
                        <form method="get" action="generate_export.php" class="mt-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="von" class="form-label">Von</label>
                                    <input type="date" class="form-control" id="von" name="von" 
                                           value="<?= htmlspecialchars($von) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="bis" class="form-label">Bis</label>
                                    <input type="date" class="form-control" id="bis" name="bis" 
                                           value="<?= htmlspecialchars($bis) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="format" class="form-label">Format</label>
                                    <select class="form-select" id="format" name="format" required>
                                        <option value="xlsx">Excel (XLSX)</option>
                                        <option value="csv">CSV</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download"></i> 
                                    Export erstellen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
