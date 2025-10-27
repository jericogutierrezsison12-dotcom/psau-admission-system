<?php
/**
 * PSAU Admission System - Generate Score Upload Template
 * Script to generate an Excel template for bulk score uploads
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
// Load Composer autoload if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Check if user is logged in as admin
is_admin_logged_in();

// Fallback data shared by both XLSX and CSV
$headers = ['Control Number', 'Stanine Score', 'Remarks'];
$sampleData = [
    ['2024-0001', 7, 'Sample entry'],
    ['2024-0002', 5, ''],
    ['2024-0003', 9, ''],
];

// If PhpSpreadsheet is unavailable, generate CSV fallback
if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    // Prepare CSV output
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="score_upload_template.csv"');
    header('Cache-Control: max-age=0');

    $out = fopen('php://output', 'w');
    // Headers
    fputcsv($out, $headers);
    // Sample rows
    foreach ($sampleData as $row) {
        fputcsv($out, $row);
    }
    // Blank line and instructions
    fputcsv($out, []);
    fputcsv($out, ['Instructions:']);
    fputcsv($out, ["1. Control Number: Enter the applicant's control number"]);
    fputcsv($out, ['2. Stanine Score: Enter a score from 1 to 9']);
    fputcsv($out, ['3. Remarks: Optional notes about the score']);
    fputcsv($out, ['4. Do not modify the column headers']);
    fputcsv($out, ['5. Remove sample data before uploading']);
    fclose($out);
    exit;
}

// Create new spreadsheet (XLSX)
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('PSAU Admission System')
    ->setLastModifiedBy('PSAU Admission System')
    ->setTitle('Entrance Exam Score Upload Template')
    ->setSubject('Template for uploading entrance exam scores')
    ->setDescription('Template for bulk uploading entrance exam scores');

// Set column headers
$sheet->fromArray($headers, null, 'A1');

// Style the header row
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2E7D32'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Add data validation for stanine score
$validation = $sheet->getCell('B2')->getDataValidation();
$validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE);
$validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('1,2,3,4,5,6,7,8,9');
$validation->setErrorTitle('Invalid Score');
$validation->setError('Please enter a stanine score between 1 and 9');
$validation->setPromptTitle('Stanine Score');
$validation->setPrompt('Enter a stanine score from 1 to 9');

// Apply validation to all cells in column B
$sheet->setDataValidation('B2:B1000', $validation);

// Add sample data
$sheet->fromArray($sampleData, null, 'A2');

// Add instructions
$sheet->setCellValue('A6', 'Instructions:');
$sheet->setCellValue('A7', '1. Control Number: Enter the applicant\'s control number');
$sheet->setCellValue('A8', '2. Stanine Score: Enter a score from 1 to 9');
$sheet->setCellValue('A9', '3. Remarks: Optional notes about the score');
$sheet->setCellValue('A10', '4. Do not modify the column headers');
$sheet->setCellValue('A11', '5. Remove sample data before uploading');

// Style the instructions
$sheet->getStyle('A6:A11')->getFont()->setBold(true);
$sheet->getStyle('A7:A11')->getFont()->setBold(false);

// Auto-size columns
foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="score_upload_template.xlsx"');
header('Cache-Control: max-age=0');

// Save file to PHP output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); 