<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\XlsxImportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class XlsxImportServiceTest extends TestCase
{
    private XlsxImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new XlsxImportService();
    }

    public function test_can_guess_column_mapping_with_exact_matches(): void
    {
        $headers = ['Date', 'Description', 'Amount', 'Category'];

        $result = $this->service->guessColumnMapping($headers);

        $this->assertEquals('Date', $result['mapping_config']['date_column']);
        $this->assertEquals('Description', $result['mapping_config']['description_column']);
        $this->assertEquals('Amount', $result['mapping_config']['amount_column']);
        $this->assertEquals('Category', $result['mapping_config']['category_column']);
        $this->assertEquals(100, $result['confidence_scores']['date_column']);
        $this->assertEquals(100, $result['confidence_scores']['description_column']);
    }

    public function test_can_guess_column_mapping_with_partial_matches(): void
    {
        $headers = ['Transaction Date', 'Memo', 'Total', 'Tags'];

        $result = $this->service->guessColumnMapping($headers);

        $this->assertEquals('Transaction Date', $result['mapping_config']['date_column']);
        $this->assertEquals('Memo', $result['mapping_config']['description_column']);
        $this->assertEquals('Total', $result['mapping_config']['amount_column']);
        $this->assertEquals('Tags', $result['mapping_config']['tags_column']);
        $this->assertEquals(75, $result['confidence_scores']['date_column']);
        $this->assertEquals(75, $result['confidence_scores']['description_column']);
    }

    public function test_validates_mapping_config_for_required_fields(): void
    {
        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => null,
            'amount_column' => 'Amount',
        ];
        $headers = ['Date', 'Amount'];

        $errors = $this->service->validateMapping($mappingConfig, $headers);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Description column is required', implode(', ', $errors));
    }

    public function test_validates_mapping_requires_amount_strategy(): void
    {
        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
        ];
        $headers = ['Date', 'Description'];

        $errors = $this->service->validateMapping($mappingConfig, $headers);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('amount column or both debit/credit columns are required', implode(', ', $errors));
    }

    public function test_detects_type_from_negative_amount_single_column(): void
    {
        $row = ['Amount' => '-50.00'];
        $mappingConfig = ['amount_column' => 'Amount'];

        $type = $this->service->detectType($row, $mappingConfig);

        $this->assertEquals('debit', $type);
    }

    public function test_detects_type_from_positive_amount_single_column(): void
    {
        $row = ['Amount' => '100.00'];
        $mappingConfig = ['amount_column' => 'Amount'];

        $type = $this->service->detectType($row, $mappingConfig);

        $this->assertEquals('credit', $type);
    }

    public function test_detects_type_from_debit_credit_columns(): void
    {
        $row = ['Debit' => '50.00', 'Credit' => ''];
        $mappingConfig = ['debit_column' => 'Debit', 'credit_column' => 'Credit'];

        $type = $this->service->detectType($row, $mappingConfig);

        $this->assertEquals('debit', $type);
    }

    public function test_detects_type_from_type_column(): void
    {
        $row = ['Amount' => '50.00', 'Type' => 'expense'];
        $mappingConfig = ['amount_column' => 'Amount', 'type_column' => 'Type'];

        $type = $this->service->detectType($row, $mappingConfig);

        $this->assertEquals('debit', $type);
    }

    public function test_calculates_row_hash_consistently(): void
    {
        $date = Carbon::parse('2026-01-15');
        $amount = 50.00;
        $description = 'Grocery Shopping';

        $hash1 = $this->service->calculateRowHash($date, $amount, $description);
        $hash2 = $this->service->calculateRowHash($date, $amount, $description);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));
    }

    public function test_extracts_transaction_from_row_successfully(): void
    {
        $row = [
            'Date' => '2026-01-15',
            'Description' => 'Grocery Shopping',
            'Amount' => '-50.00',
            'Category' => 'Food',
            'Tags' => 'groceries, shopping',
        ];
        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_column' => 'Amount',
            'category_column' => 'Category',
            'tags_column' => 'Tags',
        ];

        $transaction = $this->service->extractTransactionFromRow($row, $mappingConfig);

        $this->assertEquals('2026-01-15', $transaction['transaction_date']);
        $this->assertEquals('Grocery Shopping', $transaction['description']);
        $this->assertEquals(50.00, $transaction['amount']);
        $this->assertEquals('debit', $transaction['type']);
        $this->assertEquals('Food', $transaction['category_name']);
        $this->assertEquals(['groceries', 'shopping'], $transaction['tags']);
    }
}
