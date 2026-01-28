<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use App\Models\XlsxImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\XlsxTestHelper;
use Tests\TestCase;

class XlsxImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Storage::fake('local');
    }

    private function createTestXlsxFile(string $filename = 'test.xlsx'): UploadedFile
    {
        $tempPath = storage_path("app/{$filename}");
        XlsxTestHelper::createStandardFormatXlsx($tempPath);

        return new UploadedFile($tempPath, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_can_detect_columns_from_uploaded_xlsx(): void
    {
        $file = $this->createTestXlsxFile('test_detect.xlsx');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports/detect-columns', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'headers',
                'suggested_mapping',
                'confidence_scores',
            ]);
    }

    public function test_detects_columns_requires_file(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports/detect-columns');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_preview_import_with_mapping(): void
    {
        $file = $this->createTestXlsxFile('test_preview.xlsx');

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports/preview', [
                'file' => $file,
                'mapping_config' => $mappingConfig,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'preview_transactions',
                'validation_summary' => [
                    'valid_rows',
                    'rows_with_warnings',
                ],
            ]);
    }

    public function test_preview_requires_mapping_config(): void
    {
        $file = $this->createTestXlsxFile('test_preview_validation.xlsx');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports/preview', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mapping_config']);
    }

    public function test_can_create_xlsx_import(): void
    {
        Queue::fake();

        $file = $this->createTestXlsxFile('test_create.xlsx');

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'status',
                    'total_count',
                    'processed_count',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('xlsx_imports', [
            'filename' => 'test_create.xlsx',
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_detects_duplicate_import(): void
    {
        $file = $this->createTestXlsxFile('test_duplicate.xlsx');
        $fileHash = hash_file('sha256', $file->getPathname());

        // Create existing import
        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'file_hash' => $fileHash,
            'status' => 'completed',
        ]);

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'This file has already been imported for this account.',
                'requires_confirmation' => true,
            ]);
    }

    public function test_can_force_duplicate_import(): void
    {
        Queue::fake();

        $file = $this->createTestXlsxFile('test_force_duplicate.xlsx');
        $fileHash = hash_file('sha256', $file->getPathname());

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'file_hash' => $fileHash,
            'status' => 'completed',
        ]);

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
                'force' => true,
            ]);

        $response->assertStatus(201);
    }

    public function test_can_list_xlsx_imports(): void
    {
        XlsxImport::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/xlsx-imports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'status',
                        'total_count',
                        'processed_count',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_imports_by_account(): void
    {
        $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/xlsx-imports?filter[account_id]={$this->account->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_single_import_status(): void
    {
        $import = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/xlsx-imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'status',
                    'progress_percentage',
                ],
            ]);
    }

    public function test_cannot_view_other_users_import(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

        $import = XlsxImport::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/xlsx-imports/{$import->id}");

        $response->assertStatus(403);
    }

    public function test_can_download_xlsx_template(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->get('/api/v1/xlsx-imports/template');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_save_column_mapping_during_import(): void
    {
        Queue::fake();

        $file = $this->createTestXlsxFile('test_save_mapping.xlsx');

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
                'save_mapping' => true,
                'mapping_name' => 'My Custom Mapping',
                'set_as_default' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('xlsx_column_mappings', [
            'user_id' => $this->user->id,
            'name' => 'My Custom Mapping',
            'is_default' => true,
        ]);
    }

    public function test_import_requires_authentication(): void
    {
        $file = $this->createTestXlsxFile('test_auth.xlsx');

        $response = $this->postJson('/api/v1/xlsx-imports', [
            'file' => $file,
            'account_id' => $this->account->id,
        ]);

        $response->assertStatus(401);
    }
}
