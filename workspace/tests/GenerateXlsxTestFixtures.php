<?php

declare(strict_types=1);

namespace Tests;

use Tests\Helpers\XlsxTestHelper;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Helpers/XlsxTestHelper.php';

/**
 * Generate physical XLSX test fixture files
 * Run with: php tests/GenerateXlsxTestFixtures.php
 */

$fixturesDir = __DIR__ . '/fixtures';

if (! is_dir($fixturesDir)) {
    mkdir($fixturesDir, 0755, true);
}

echo "Generating XLSX test fixtures...\n";

// 1. Standard format (single amount column)
$standardFile = $fixturesDir . '/valid_statement.xlsx';
XlsxTestHelper::createStandardFormatXlsx($standardFile);
echo "✓ Created: valid_statement.xlsx\n";

// 2. Debit/Credit format (separate columns)
$debitCreditFile = $fixturesDir . '/debit_credit_format.xlsx';
XlsxTestHelper::createDebitCreditFormatXlsx($debitCreditFile);
echo "✓ Created: debit_credit_format.xlsx\n";

// 3. Type column format
$typeColumnFile = $fixturesDir . '/type_column_format.xlsx';
XlsxTestHelper::createTypeColumnFormatXlsx($typeColumnFile);
echo "✓ Created: type_column_format.xlsx\n";

// 4. CSV format
$csvFile = $fixturesDir . '/valid_statement.csv';
XlsxTestHelper::createStandardFormatCsv($csvFile);
echo "✓ Created: valid_statement.csv\n";

// 5. Invalid format (no headers)
$invalidFile = $fixturesDir . '/invalid.xlsx';
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', '2026-01-15');
$sheet->setCellValue('B1', 'Test');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save($invalidFile);
echo "✓ Created: invalid.xlsx\n";

// 6. Large file (1000 rows)
$largeFile = $fixturesDir . '/large.xlsx';
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Date');
$sheet->setCellValue('B1', 'Description');
$sheet->setCellValue('C1', 'Amount');
$sheet->setCellValue('D1', 'Category');

// Generate 1000 rows
for ($i = 2; $i <= 1001; $i++) {
    $date = date('Y-m-d', strtotime('2026-01-01 +' . ($i - 2) . ' days'));
    $amount = rand(-20000, 50000) / 100;

    $sheet->setCellValue('A' . $i, $date);
    $sheet->setCellValue('B' . $i, 'Transaction ' . ($i - 1));
    $sheet->setCellValue('C' . $i, $amount);
    $sheet->setCellValue('D' . $i, $amount < 0 ? 'Expense' : 'Income');
}

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save($largeFile);
echo "✓ Created: large.xlsx (1000 rows)\n";

echo "\nAll test fixtures generated successfully in tests/fixtures/\n";
