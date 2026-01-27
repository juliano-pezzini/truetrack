<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\CategoryType;
use App\Enums\ReconciliationStatus;
use App\Jobs\ProcessXlsxImport;
use App\Models\Account;
use App\Models\Category;
use App\Models\Reconciliation;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\XlsxImport;
use App\Services\ReconciliationService;
use App\Services\XlsxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProcessXlsxImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private array $mappingConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'bank',
        ]);

        $this->mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_column' => 'Amount',
            'amount_strategy' => 'single_column',
        ];
    }

    public function test_resolves_category_id_creates_new_category(): void
    {
        $xlsxImport = XlsxImport::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $job = new ProcessXlsxImport(
            xlsxImportId: $xlsxImport->id,
            accountId: $this->account->id,
            userId: $this->user->id,
            mappingConfig: $this->mappingConfig
        );

        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('resolveCategoryId');
        $method->setAccessible(true);

        $categoryId = $method->invoke($job, 'Groceries');

        $this->assertDatabaseHas('categories', [
            'id' => $categoryId,
            'user_id' => $this->user->id,
            'name' => 'Groceries',
            'type' => CategoryType::EXPENSE->value,
        ]);
    }

    public function test_resolves_category_id_returns_existing_category(): void
    {
        $existingCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Groceries',
            'type' => CategoryType::EXPENSE,
            'is_active' => true,
        ]);

        $xlsxImport = XlsxImport::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $job = new ProcessXlsxImport(
            xlsxImportId: $xlsxImport->id,
            accountId: $this->account->id,
            userId: $this->user->id,
            mappingConfig: $this->mappingConfig
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('resolveCategoryId');
        $method->setAccessible(true);

        $categoryId = $method->invoke($job, 'Groceries');

        $this->assertEquals($existingCategory->id, $categoryId);
        $this->assertCount(1, Category::where('name', 'Groceries')->get());
    }

    public function test_resolves_tag_ids_creates_new_tags(): void
    {
        $xlsxImport = XlsxImport::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $job = new ProcessXlsxImport(
            xlsxImportId: $xlsxImport->id,
            accountId: $this->account->id,
            userId: $this->user->id,
            mappingConfig: $this->mappingConfig
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('resolveTagIds');
        $method->setAccessible(true);

        $tagIds = $method->invoke($job, ['vacation', 'travel']);

        $this->assertCount(2, $tagIds);
        $this->assertDatabaseHas('tags', [
            'user_id' => $this->user->id,
            'name' => 'vacation',
        ]);
        $this->assertDatabaseHas('tags', [
            'user_id' => $this->user->id,
            'name' => 'travel',
        ]);
    }

    public function test_resolves_tag_ids_returns_existing_tags(): void
    {
        $tag1 = Tag::create([
            'user_id' => $this->user->id,
            'name' => 'vacation',
            'color' => 'blue',
        ]);

        $tag2 = Tag::create([
            'user_id' => $this->user->id,
            'name' => 'travel',
            'color' => 'green',
        ]);

        $xlsxImport = XlsxImport::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $job = new ProcessXlsxImport(
            xlsxImportId: $xlsxImport->id,
            accountId: $this->account->id,
            userId: $this->user->id,
            mappingConfig: $this->mappingConfig
        );

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('resolveTagIds');
        $method->setAccessible(true);

        $tagIds = $method->invoke($job, ['vacation', 'travel']);

        $this->assertCount(2, $tagIds);
        $this->assertContains($tag1->id, $tagIds);
        $this->assertContains($tag2->id, $tagIds);
        $this->assertCount(2, Tag::all());
    }

    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        $xlsxImport = XlsxImport::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        ProcessXlsxImport::dispatch(
            xlsxImportId: $xlsxImport->id,
            accountId: $this->account->id,
            userId: $this->user->id,
            mappingConfig: $this->mappingConfig
        );

        Queue::assertPushed(ProcessXlsxImport::class);
    }
}

