<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Setting;
use App\Models\User;
use App\Services\OfxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfxImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OfxImportService $service;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = new OfxImportService();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
    }

    public function test_can_compress_and_store_file(): void
    {
        $content = "OFXHEADER:100\nDATA:VERSION:102\n";
        $tempPath = Storage::path('temp/test.ofx');
        Storage::put('temp/test.ofx', $content);

        $result = $this->service->compressAndStoreFile(
            $tempPath,
            $this->account->id,
            $this->user->id
        );

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertEquals(64, strlen($result['hash'])); // SHA-256 length
        $this->assertTrue(Storage::exists($result['path']));
        $this->assertStringContainsString('.ofx.gz', $result['path']);
    }

    public function test_can_decompress_file(): void
    {
        $content = "OFXHEADER:100\nDATA:VERSION:102\n";
        $compressed = gzencode($content, 9);
        $compressedPath = 'test/compressed.ofx.gz';
        Storage::put($compressedPath, $compressed);

        $decompressedPath = $this->service->decompressFile($compressedPath);

        $this->assertTrue(Storage::exists($decompressedPath));
        $this->assertEquals($content, Storage::get($decompressedPath));
    }

    public function test_decompress_file_throws_exception_for_missing_file(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Compressed file not found');

        $this->service->decompressFile('nonexistent/file.ofx.gz');
    }

    public function test_can_check_duplicate_import(): void
    {
        $fileHash = hash('sha256', 'test content');

        $existingImport = OfxImport::factory()->create([
            'file_hash' => $fileHash,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        $duplicate = $this->service->checkDuplicateImport($fileHash, $this->account->id);

        $this->assertNotNull($duplicate);
        $this->assertEquals($existingImport->id, $duplicate->id);
    }

    public function test_duplicate_check_returns_null_for_new_file(): void
    {
        $fileHash = hash('sha256', 'unique content');

        $duplicate = $this->service->checkDuplicateImport($fileHash, $this->account->id);

        $this->assertNull($duplicate);
    }

    public function test_duplicate_check_ignores_failed_imports(): void
    {
        $fileHash = hash('sha256', 'test content');

        OfxImport::factory()->create([
            'file_hash' => $fileHash,
            'account_id' => $this->account->id,
            'status' => 'failed',
        ]);

        $duplicate = $this->service->checkDuplicateImport($fileHash, $this->account->id);

        $this->assertNull($duplicate);
    }

    public function test_can_check_concurrency_limit(): void
    {
        Setting::create([
            'key' => 'max_concurrent_imports_per_user',
            'value' => '2',
            'type' => 'integer',
            'category' => 'import',
        ]);

        // Create 2 active imports
        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
        ]);

        $limitReached = $this->service->checkConcurrencyLimit($this->user->id);

        $this->assertTrue($limitReached);
    }

    public function test_concurrency_limit_false_when_under_limit(): void
    {
        Setting::create([
            'key' => 'max_concurrent_imports_per_user',
            'value' => '5',
            'type' => 'integer',
            'category' => 'import',
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
        ]);

        $limitReached = $this->service->checkConcurrencyLimit($this->user->id);

        $this->assertFalse($limitReached);
    }

    public function test_can_create_import(): void
    {
        $data = [
            'filename' => 'test.ofx',
            'file_hash' => hash('sha256', 'test'),
            'account_id' => $this->account->id,
            'file_path' => 'ofx_imports/test.ofx.gz',
            'user_id' => $this->user->id,
        ];

        $import = $this->service->createImport($data);

        $this->assertInstanceOf(OfxImport::class, $import);
        $this->assertEquals('test.ofx', $import->filename);
        $this->assertEquals('pending', $import->status);
        $this->assertEquals(0, $import->processed_count);
        $this->assertEquals(0, $import->total_count);
    }

    public function test_map_transaction_type_returns_credit_for_positive(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapTransactionType');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 100.50);

        $this->assertEquals('credit', $result);
    }

    public function test_map_transaction_type_returns_debit_for_negative(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapTransactionType');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, -50.75);

        $this->assertEquals('debit', $result);
    }

    public function test_map_transaction_type_returns_credit_for_zero(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapTransactionType');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 0.0);

        $this->assertEquals('credit', $result);
    }
}
