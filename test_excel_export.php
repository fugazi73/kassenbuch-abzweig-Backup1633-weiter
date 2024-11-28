<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

function generateExcel($data, $filename, $kassenstand) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Überschrift und Datum
    $sheet->setCellValue('A1', 'Kassenbuch - Einnahmen und Ausgaben');
    $sheet->mergeCells('A1:F1');
    
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
    $sheet->mergeCells('D2:F2');
    
    // Kassenstand
    $sheet->setCellValue('A3', 'Kassenstand zu Beginn:');
    $sheet->mergeCells('A3:C3');
    $sheet->setCellValue('D3', $kassenstand);
    $sheet->mergeCells('D3:F3');
    
    // Styling für Überschriften
    $sheet->getStyle('A1:F3')->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    $sheet->getStyle('A1')->getFont()->setSize(14);
    $sheet->getStyle('D3')->getNumberFormat()->setFormatCode('#,##0.00 €');
    
    // Spaltenüberschriften
    $headers = ['Datum', 'Beleg', 'Bemerkung', 'Einnahme', 'Ausgabe', 'Saldo'];
    $sheet->fromArray($headers, NULL, 'A5');
    
    // Header-Styling
    $sheet->getStyle('A5:F5')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => '4472C4']
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);
    
    // Daten einfügen
    $sheet->fromArray($data, NULL, 'A6');
    $lastRow = $sheet->getHighestRow();
    
    // Gesamtsummen
    $sumRow = $lastRow + 2;
    $sheet->setCellValue('C'.$sumRow, 'Gesamtsummen:');
    $sheet->setCellValue('D'.$sumRow, '=SUM(D6:D'.$lastRow.')');
    $sheet->setCellValue('E'.$sumRow, '=SUM(E6:E'.$lastRow.')');
    $sheet->setCellValue('F'.$sumRow, '=D'.$sumRow.'-E'.$sumRow);
    
    // Monatssummen
    $monthSumRow = $sumRow + 2;
    $sheet->setCellValue('C'.$monthSumRow, "Summen für $monthName $year:");
    $sheet->setCellValue('D'.$monthSumRow, '=SUM(D6:D'.$lastRow.')');
    $sheet->setCellValue('E'.$monthSumRow, '=SUM(E6:E'.$lastRow.')');
    $sheet->setCellValue('F'.$monthSumRow, '=D'.$monthSumRow.'-E'.$monthSumRow);
    
    // Aktueller Saldo
    $currentSaldoRow = $monthSumRow + 2;
    $sheet->setCellValue('C'.$currentSaldoRow, 'Aktueller Saldo:');
    $sheet->setCellValue('D'.$currentSaldoRow, '='.$kassenstand.'+D'.$sumRow.'-E'.$sumRow);
    $sheet->mergeCells('D'.$currentSaldoRow.':F'.$currentSaldoRow);
    
    // Styling für Summenzeilen
    foreach([$sumRow, $monthSumRow, $currentSaldoRow] as $row) {
        $sheet->getStyle('C'.$row.':F'.$row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'E0E0E0']
            ],
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
        $sheet->getStyle('D'.$row.':F'.$row)->getNumberFormat()->setFormatCode('#,##0.00 €');
    }
    
    // Spaltenbreiten anpassen
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(15);
    
    // Excel-Datei erstellen und herunterladen
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
}

// Beispielaufruf
$kassenstand = 1000.00;
$data = [
    ['2024-11-05', '', 'Amanda', 0, 12, -12],
    ['2024-11-05', '', 'immer wieder', 0, 44, -44],
    // ... weitere Daten ...
];

generateExcel($data, 'kassenbuch_export_'.date('Y-m').'.xlsx', $kassenstand);