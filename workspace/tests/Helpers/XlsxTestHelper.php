<?php

declare(strict_types=1);

namespace Tests\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxTestHelper
{
    /**
     * Create a test XLSX file with the standard format.
     */
    public static function createStandardFormatXlsx(string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');
        $sheet->setCellValue('E1', 'Tags');

        // Sample data rows
        $sheet->setCellValue('A2', '2026-01-15');
        $sheet->setCellValue('B2', 'Grocery Shopping');
        $sheet->setCellValue('C2', -150.50);
        $sheet->setCellValue('D2', 'Groceries');
        $sheet->setCellValue('E2', 'food, weekly');

        $sheet->setCellValue('A3', '2026-01-16');
        $sheet->setCellValue('B3', 'Salary Deposit');
        $sheet->setCellValue('C3', 5000.00);
        $sheet->setCellValue('D3', 'Income');
        $sheet->setCellValue('E3', 'salary');

        $sheet->setCellValue('A4', '2026-01-17');
        $sheet->setCellValue('B4', 'Electric Bill');
        $sheet->setCellValue('C4', -89.99);
        $sheet->setCellValue('D4', 'Utilities');
        $sheet->setCellValue('E4', '');

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    /**
     * Create a test XLSX file with debit/credit columns.
     */
    public static function createDebitCreditFormatXlsx(string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Debit');
        $sheet->setCellValue('D1', 'Credit');
        $sheet->setCellValue('E1', 'Category');

        // Sample data rows
        $sheet->setCellValue('A2', '2026-01-15');
        $sheet->setCellValue('B2', 'Grocery Shopping');
        $sheet->setCellValue('C2', 150.50);
        $sheet->setCellValue('D2', '');
        $sheet->setCellValue('E2', 'Groceries');

        $sheet->setCellValue('A3', '2026-01-16');
        $sheet->setCellValue('B3', 'Salary Deposit');
        $sheet->setCellValue('C3', '');
        $sheet->setCellValue('D3', 5000.00);
        $sheet->setCellValue('E3', 'Income');

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    /**
     * Create a test XLSX file with type column.
     */
    public static function createTypeColumnFormatXlsx(string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Memo');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Type');
        $sheet->setCellValue('E1', 'Settled Date');

        // Sample data rows
        $sheet->setCellValue('A2', '2026-01-15');
        $sheet->setCellValue('B2', 'Grocery Shopping');
        $sheet->setCellValue('C2', 150.50);
        $sheet->setCellValue('D2', 'debit');
        $sheet->setCellValue('E2', '2026-01-17');

        $sheet->setCellValue('A3', '2026-01-16');
        $sheet->setCellValue('B3', 'Salary Deposit');
        $sheet->setCellValue('C3', 5000.00);
        $sheet->setCellValue('D3', 'credit');
        $sheet->setCellValue('E3', '2026-01-16');

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    /**
     * Create a test CSV file with standard format.
     */
    public static function createStandardFormatCsv(string $path): void
    {
        $data = [
            ['Date', 'Description', 'Amount', 'Category', 'Tags'],
            ['2026-01-15', 'Grocery Shopping', '-150.50', 'Groceries', 'food, weekly'],
            ['2026-01-16', 'Salary Deposit', '5000.00', 'Income', 'salary'],
            ['2026-01-17', 'Electric Bill', '-89.99', 'Utilities', ''],
        ];

        $handle = fopen($path, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    /**
     * Create an invalid XLSX file (corrupted).
     */
    public static function createInvalidXlsx(string $path): void
    {
        file_put_contents($path, 'This is not a valid XLSX file content');
    }

    /**
     * Create a large XLSX file for performance testing.
     */
    public static function createLargeXlsx(string $path, int $rowCount = 1000): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');

        // Generate many rows
        for ($i = 2; $i <= $rowCount + 1; $i++) {
            $date = '2026-01-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT);
            $sheet->setCellValue("A{$i}", $date);
            $sheet->setCellValue("B{$i}", "Transaction {$i}");
            $sheet->setCellValue("C{$i}", rand(-500, 500) / 10);
            $sheet->setCellValue("D{$i}", $i % 2 === 0 ? 'Expenses' : 'Income');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}
