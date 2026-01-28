<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use App\Models\XlsxColumnMapping;
use App\Models\XlsxImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XlsxImportModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_xlsx_import(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $import = XlsxImport::create([
            'filename' => 'test.xlsx',
            'file_hash' => hash('sha256', 'test'),
            'account_id' => $account->id,
            'status' => 'pending',
            'file_path' => 'xlsx_imports/test.xlsx.gz',
            'user_id' => $user->id,
            'total_count' => 100,
        ]);

        $this->assertDatabaseHas('xlsx_imports', [
            'filename' => 'test.xlsx',
            'status' => 'pending',
        ]);
        $this->assertEquals(100, $import->total_count);
    }

    public function test_calculates_progress_percentage_correctly(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $import = XlsxImport::create([
            'filename' => 'test.xlsx',
            'file_hash' => hash('sha256', 'test'),
            'account_id' => $account->id,
            'status' => 'processing',
            'file_path' => 'xlsx_imports/test.xlsx.gz',
            'user_id' => $user->id,
            'total_count' => 100,
            'processed_count' => 50,
        ]);

        $this->assertEquals(50.0, $import->getProgressPercentage());
    }

    public function test_status_methods_work_correctly(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $import = XlsxImport::create([
            'filename' => 'test.xlsx',
            'file_hash' => hash('sha256', 'test'),
            'account_id' => $account->id,
            'status' => 'completed',
            'file_path' => 'xlsx_imports/test.xlsx.gz',
            'user_id' => $user->id,
            'total_count' => 100,
            'processed_count' => 100,
        ]);

        $this->assertTrue($import->isCompleted());
        $this->assertFalse($import->isFailed());
        $this->assertFalse($import->isProcessing());
    }

    public function test_can_create_xlsx_column_mapping(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $mapping = XlsxColumnMapping::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'name' => 'Chase Bank Format',
            'mapping_config' => [
                'date_column' => 'Date',
                'description_column' => 'Description',
                'amount_column' => 'Amount',
            ],
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('xlsx_column_mappings', [
            'name' => 'Chase Bank Format',
        ]);
        $this->assertIsArray($mapping->mapping_config);
        $this->assertEquals('Date', $mapping->mapping_config['date_column']);
    }

    public function test_mark_as_used_updates_timestamp(): void
    {
        $user = User::factory()->create();
        $mapping = XlsxColumnMapping::create([
            'user_id' => $user->id,
            'name' => 'Test Mapping',
            'mapping_config' => ['date_column' => 'Date'],
            'is_default' => false,
        ]);

        $this->assertNull($mapping->last_used_at);

        $mapping->markAsUsed();
        $mapping->refresh();

        $this->assertNotNull($mapping->last_used_at);
    }

    public function test_set_as_default_clears_other_defaults(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $mapping1 = XlsxColumnMapping::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'name' => 'Mapping 1',
            'mapping_config' => ['date_column' => 'Date'],
            'is_default' => true,
        ]);

        $mapping2 = XlsxColumnMapping::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'name' => 'Mapping 2',
            'mapping_config' => ['date_column' => 'Date'],
            'is_default' => false,
        ]);

        $mapping2->setAsDefault();
        $mapping1->refresh();

        $this->assertFalse($mapping1->is_default);
        $this->assertTrue($mapping2->is_default);
    }
}
