<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidRowDataException;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxImportService
{
    /**
     * Parse XLSX file and return headers, row count, and preview.
     *
     * @return array{headers: array<string>, row_count: int, preview_rows: array<array>}
     */
    public function parseXlsxFile(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        // Detect headers (first non-empty row)
        $headers = $this->detectHeaders($file);

        // Get all rows
        $rows = $worksheet->toArray();

        // Find header row index and skip it
        $headerRowIndex = 0;
        foreach ($rows as $index => $row) {
            if (! empty(array_filter($row))) {
                $headerRowIndex = $index;
                break;
            }
        }

        // Get data rows (skip header)
        $dataRows = array_slice($rows, $headerRowIndex + 1);
        $dataRows = array_filter($dataRows, fn ($row) => ! empty(array_filter($row))); // Remove empty rows

        // Preview first 5 rows
        $previewRows = array_slice($dataRows, 0, 5);

        return [
            'headers' => $headers,
            'row_count' => count($dataRows),
            'preview_rows' => $previewRows,
        ];
    }

    /**
     * Detect headers from the first non-empty row.
     *
     * @return array<string>
     */
    public function detectHeaders(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Find first non-empty row
        foreach ($rows as $row) {
            if (! empty(array_filter($row))) {
                return array_map(fn ($header) => trim((string) $header), $row);
            }
        }

        return [];
    }

    /**
     * Guess column mapping using smart heuristics.
     *
     * @param  array<string>  $headers
     * @return array{mapping_config: array, confidence_scores: array<string, int>}
     */
    public function guessColumnMapping(array $headers): array
    {
        $mapping = [
            'date_column' => null,
            'description_column' => null,
            'amount_column' => null,
            'debit_column' => null,
            'credit_column' => null,
            'type_column' => null,
            'category_column' => null,
            'settled_date_column' => null,
            'tags_column' => null,
        ];

        $confidenceScores = [];

        foreach ($headers as $header) {
            $headerLower = strtolower($header);

            // Transaction Date
            if (! $mapping['date_column'] && (
                $headerLower === 'date' ||
                str_contains($headerLower, 'transaction date') ||
                str_contains($headerLower, 'trans date')
            )) {
                $mapping['date_column'] = $header;
                $confidenceScores['date_column'] = $headerLower === 'date' ? 100 : 75;
            }

            // Description
            if (! $mapping['description_column'] && (
                $headerLower === 'description' ||
                $headerLower === 'memo' ||
                str_contains($headerLower, 'details')
            )) {
                $mapping['description_column'] = $header;
                $confidenceScores['description_column'] = $headerLower === 'description' ? 100 : 75;
            }

            // Amount
            if (! $mapping['amount_column'] && (
                $headerLower === 'amount' ||
                $headerLower === 'total'
            )) {
                $mapping['amount_column'] = $header;
                $confidenceScores['amount_column'] = $headerLower === 'amount' ? 100 : 75;
            }

            // Debit
            if (! $mapping['debit_column'] && (
                $headerLower === 'debit' ||
                $headerLower === 'withdrawal'
            )) {
                $mapping['debit_column'] = $header;
                $confidenceScores['debit_column'] = $headerLower === 'debit' ? 100 : 75;
            }

            // Credit
            if (! $mapping['credit_column'] && (
                $headerLower === 'credit' ||
                $headerLower === 'deposit'
            )) {
                $mapping['credit_column'] = $header;
                $confidenceScores['credit_column'] = $headerLower === 'credit' ? 100 : 75;
            }

            // Type
            if (! $mapping['type_column'] && (
                $headerLower === 'type' ||
                str_contains($headerLower, 'transaction type')
            )) {
                $mapping['type_column'] = $header;
                $confidenceScores['type_column'] = $headerLower === 'type' ? 100 : 75;
            }

            // Category
            if (! $mapping['category_column'] && $headerLower === 'category') {
                $mapping['category_column'] = $header;
                $confidenceScores['category_column'] = 100;
            }

            // Settled Date
            if (! $mapping['settled_date_column'] && (
                str_contains($headerLower, 'settled') ||
                str_contains($headerLower, 'posted date')
            )) {
                $mapping['settled_date_column'] = $header;
                $confidenceScores['settled_date_column'] = 75;
            }

            // Tags
            if (! $mapping['tags_column'] && (
                $headerLower === 'tags' ||
                $headerLower === 'labels'
            )) {
                $mapping['tags_column'] = $header;
                $confidenceScores['tags_column'] = $headerLower === 'tags' ? 100 : 75;
            }
        }

        return [
            'mapping_config' => $mapping,
            'confidence_scores' => $confidenceScores,
        ];
    }

    /**
     * Validate mapping configuration.
     *
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateMapping(array $mappingConfig, array $headers): array
    {
        $errors = [];

        // Check required fields
        if (empty($mappingConfig['date_column'])) {
            $errors[] = 'Transaction date column is required';
        }

        if (empty($mappingConfig['description_column'])) {
            $errors[] = 'Description column is required';
        }

        // Determine amount strategy
        $hasAmount = ! empty($mappingConfig['amount_column']);
        $hasDebitCredit = ! empty($mappingConfig['debit_column']) && ! empty($mappingConfig['credit_column']);
        $hasType = ! empty($mappingConfig['type_column']);

        if (! $hasAmount && ! $hasDebitCredit) {
            $errors[] = 'Either amount column or both debit/credit columns are required';
        }

        if ($hasType && ! $hasAmount) {
            $errors[] = 'Type column requires amount column';
        }

        // Verify mapped columns exist in headers (skip strategy keys)
        $columnKeys = ['date_column', 'description_column', 'amount_column', 'debit_column', 'credit_column', 'type_column', 'category_column', 'settled_date_column', 'tags_column'];

        foreach ($columnKeys as $key) {
            $column = $mappingConfig[$key] ?? null;
            if ($column && ! in_array($column, $headers, true)) {
                $errors[] = "Mapped column '{$column}' not found in spreadsheet headers";
            }
        }

        return $errors;
    }

    /**
     * Preview with mapping applied to first 5 rows.
     *
     * @return array{preview_transactions: array, validation_summary: array}
     */
    public function previewWithMapping(UploadedFile $file, array $mappingConfig): array
    {
        $parsed = $this->parseXlsxFile($file);
        $previewRows = array_slice($parsed['preview_rows'], 0, 5);
        $headers = $parsed['headers'];

        $previewTransactions = [];
        $validRows = 0;
        $rowsWithWarnings = 0;

        foreach ($previewRows as $index => $row) {
            $rowData = array_combine($headers, $row);
            $warnings = [];

            try {
                $transaction = $this->extractTransactionFromRow($rowData, $mappingConfig);
                $validRows++;
            } catch (\Exception $e) {
                $warnings[] = $e->getMessage();
                $rowsWithWarnings++;
                $transaction = $this->extractTransactionFromRowSafe($rowData, $mappingConfig);
            }

            $transaction['warnings'] = $warnings;
            $previewTransactions[] = $transaction;
        }

        return [
            'preview_transactions' => $previewTransactions,
            'validation_summary' => [
                'valid_rows' => $validRows,
                'rows_with_warnings' => $rowsWithWarnings,
            ],
        ];
    }

    /**
     * Extract transaction data from a single row.
     *
     * @throws InvalidRowDataException
     */
    public function extractTransactionFromRow(array $row, array $mappingConfig): array
    {
        $dateColumn = $mappingConfig['date_column'];
        $descriptionColumn = $mappingConfig['description_column'];

        // Parse date
        try {
            $transactionDate = $this->parseDate($row[$dateColumn] ?? '');
        } catch (\Exception $e) {
            throw new InvalidRowDataException("Invalid date format: {$row[$dateColumn]}");
        }

        // Get description
        $description = trim($row[$descriptionColumn] ?? '');
        if (empty($description)) {
            throw new InvalidRowDataException('Description is required');
        }

        // Detect amount and type
        $amount = $this->extractAmount($row, $mappingConfig);
        $type = $this->detectType($row, $mappingConfig);

        return [
            'transaction_date' => $transactionDate->format('Y-m-d'),
            'description' => $description,
            'amount' => abs($amount),
            'type' => $type,
            'category_name' => $row[$mappingConfig['category_column']] ?? null,
            'settled_date' => isset($mappingConfig['settled_date_column']) && isset($row[$mappingConfig['settled_date_column']])
                ? $this->parseDate($row[$mappingConfig['settled_date_column']])?->format('Y-m-d')
                : null,
            'tags' => $this->parseTags($row[$mappingConfig['tags_column']] ?? ''),
        ];
    }

    /**
     * Safe extraction that returns partial data even with errors.
     */
    private function extractTransactionFromRowSafe(array $row, array $mappingConfig): array
    {
        try {
            return $this->extractTransactionFromRow($row, $mappingConfig);
        } catch (\Exception $e) {
            return [
                'transaction_date' => null,
                'description' => $row[$mappingConfig['description_column']] ?? '',
                'amount' => 0,
                'type' => 'debit',
                'category_name' => null,
                'settled_date' => null,
                'tags' => [],
            ];
        }
    }

    /**
     * Detect transaction type based on strategy.
     *
     * @throws InvalidRowDataException
     */
    public function detectType(array $row, array $mappingConfig): string
    {
        // Strategy C: Type column
        if (! empty($mappingConfig['type_column'])) {
            $typeValue = strtolower(trim($row[$mappingConfig['type_column']] ?? ''));

            if (in_array($typeValue, ['debit', 'expense', 'withdrawal'])) {
                return 'debit';
            }

            if (in_array($typeValue, ['credit', 'income', 'deposit'])) {
                return 'credit';
            }

            throw new InvalidRowDataException("Cannot determine transaction type from value: {$typeValue}");
        }

        // Strategy B: Separate debit/credit columns
        if (! empty($mappingConfig['debit_column']) && ! empty($mappingConfig['credit_column'])) {
            $debit = $row[$mappingConfig['debit_column']] ?? '';
            $credit = $row[$mappingConfig['credit_column']] ?? '';

            $debitValue = is_numeric($debit) ? (float) $debit : 0.0;
            $creditValue = is_numeric($credit) ? (float) $credit : 0.0;

            $hasDebit = $debitValue > 0;
            $hasCredit = $creditValue > 0;

            if ($hasDebit && ! $hasCredit) {
                return 'debit';
            }

            if ($hasCredit && ! $hasDebit) {
                return 'credit';
            }

            throw new InvalidRowDataException('Both debit and credit columns have values or both are empty');
        }

        // Strategy A: Single amount column (negative = debit)
        if (! empty($mappingConfig['amount_column'])) {
            $amount = (float) ($row[$mappingConfig['amount_column']] ?? 0);

            return $amount < 0 ? 'debit' : 'credit';
        }

        throw new InvalidRowDataException('Cannot determine transaction type - no valid strategy configured');
    }

    /**
     * Extract amount from row based on mapping configuration.
     */
    private function extractAmount(array $row, array $mappingConfig): float
    {
        // Separate columns
        if (! empty($mappingConfig['debit_column']) && ! empty($mappingConfig['credit_column'])) {
            $debit = (float) ($row[$mappingConfig['debit_column']] ?? 0);
            $credit = (float) ($row[$mappingConfig['credit_column']] ?? 0);

            return $debit ?: $credit;
        }

        // Single column
        if (! empty($mappingConfig['amount_column'])) {
            return (float) ($row[$mappingConfig['amount_column']] ?? 0);
        }

        throw new InvalidRowDataException('No amount column configured');
    }

    /**
     * Calculate row hash for duplicate detection.
     */
    public function calculateRowHash(Carbon $date, float $amount, string $description): string
    {
        $data = $date->format('Y-m-d').'|'.$amount.'|'.strtolower(trim($description));

        return hash('sha256', $data);
    }

    /**
     * Check if row is a duplicate.
     *
     * @return int|null Transaction ID if duplicate found
     */
    public function checkRowDuplicate(int $userId, int $accountId, string $rowHash): ?int
    {
        $result = DB::table('xlsx_transaction_hashes')
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->where('row_hash', $rowHash)
            ->first();

        return $result ? $result->transaction_id : null;
    }

    /**
     * Compress and store XLSX file.
     */
    public function compressAndStoreFile(UploadedFile $file): string
    {
        $filename = $file->getClientOriginalName();
        $compressedFilename = pathinfo($filename, PATHINFO_FILENAME).'_'.time().'.xlsx.gz';

        // Read file content
        $content = file_get_contents($file->getPathname());

        // Compress with gzip
        $compressed = gzencode($content, 9);

        // Store in storage/app/xlsx_imports/
        $path = 'xlsx_imports/'.$compressedFilename;
        Storage::put($path, $compressed);

        return $path;
    }

    /**
     * Generate XLSX template with example data.
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers (Row 1)
        $sheet->setCellValue('A1', 'Transaction Date (Required)');
        $sheet->setCellValue('B1', 'Description (Required)');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Debit');
        $sheet->setCellValue('E1', 'Credit');
        $sheet->setCellValue('F1', 'Type');
        $sheet->setCellValue('G1', 'Category (Optional)');
        $sheet->setCellValue('H1', 'Settled Date (Optional)');
        $sheet->setCellValue('I1', 'Tags (Optional)');

        // Example data (Row 2)
        $sheet->setCellValue('A2', '2026-01-15');
        $sheet->setCellValue('B2', 'Grocery Shopping');
        $sheet->setCellValue('C2', '-50.00');
        $sheet->setCellValue('D2', '');
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', 'debit');
        $sheet->setCellValue('G2', 'Food');
        $sheet->setCellValue('H2', '2026-01-16');
        $sheet->setCellValue('I2', 'groceries, shopping');

        // Format hints (Row 3)
        $sheet->setCellValue('A3', 'YYYY-MM-DD or MM/DD/YYYY');
        $sheet->setCellValue('B3', 'Transaction description');
        $sheet->setCellValue('C3', 'Negative for expenses');
        $sheet->setCellValue('D3', 'Use if separate columns');
        $sheet->setCellValue('E3', 'Use if separate columns');
        $sheet->setCellValue('F3', 'debit or credit');
        $sheet->setCellValue('G3', 'Category name');
        $sheet->setCellValue('H3', 'Date format');
        $sheet->setCellValue('I3', 'Comma separated');

        // Save to temp file
        $tempPath = storage_path('app/temp/xlsx_template.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Generate error report CSV.
     */
    public function generateErrorReport(array $errors): string
    {
        $filename = 'error_report_'.time().'.csv';
        $path = 'xlsx_imports/errors/'.$filename;

        $csv = "Row Number,Field,Error Message,Raw Value\n";

        foreach ($errors as $error) {
            $csv .= sprintf(
                "%d,%s,%s,%s\n",
                $error['row_number'],
                $error['field'],
                str_replace('"', '""', $error['message']),
                str_replace('"', '""', $error['raw_value'] ?? '')
            );
        }

        Storage::put($path, $csv);

        return $path;
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate(string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        // Try common formats
        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try default parsing
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Unable to parse date: {$dateString}");
        }
    }

    /**
     * Parse comma-separated tags.
     *
     * @return array<string>
     */
    private function parseTags(string $tagsString): array
    {
        if (empty($tagsString)) {
            return [];
        }

        return array_map('trim', explode(',', $tagsString));
    }
}
