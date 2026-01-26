<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\OfxImport;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupExpiredOfxImportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Setting::create([
            'key' => 'ofx_import_retention_days',
            'value' => '90',
            'type' => 'integer',
            'category' => 'import',
        ]);
    }

    public function test_deletes_expired_completed_imports(): void
    {
        $expiredImport = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(100),
            'file_path' => 'ofx_imports/old.ofx.gz',
        ]);

        Storage::put($expiredImport->file_path, 'test content');

        $recentImport = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful()
            ->expectsOutput('Found 1 expired import(s).')
            ->expectsOutput('Successfully deleted 1 file(s) and 1 database record(s).');

        $this->assertDatabaseMissing('ofx_imports', ['id' => $expiredImport->id]);
        $this->assertDatabaseHas('ofx_imports', ['id' => $recentImport->id]);
        $this->assertFalse(Storage::exists($expiredImport->file_path));
    }

    public function test_deletes_expired_failed_imports(): void
    {
        $expiredImport = OfxImport::factory()->create([
            'status' => 'failed',
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('ofx_imports', ['id' => $expiredImport->id]);
    }

    public function test_does_not_delete_pending_or_processing_imports(): void
    {
        $pendingImport = OfxImport::factory()->create([
            'status' => 'pending',
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $processingImport = OfxImport::factory()->create([
            'status' => 'processing',
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful()
            ->expectsOutput('No expired OFX imports found.');

        $this->assertDatabaseHas('ofx_imports', ['id' => $pendingImport->id]);
        $this->assertDatabaseHas('ofx_imports', ['id' => $processingImport->id]);
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $expiredImport = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(100),
            'file_path' => 'ofx_imports/old.ofx.gz',
        ]);

        Storage::put($expiredImport->file_path, 'test content');

        $this->artisan('ofx:cleanup --dry-run')
            ->assertSuccessful()
            ->expectsOutput('DRY RUN: No files will be deleted.');

        $this->assertDatabaseHas('ofx_imports', ['id' => $expiredImport->id]);
        $this->assertTrue(Storage::exists($expiredImport->file_path));
    }

    public function test_uses_retention_days_from_settings(): void
    {
        Setting::where('key', 'ofx_import_retention_days')->update(['value' => '30']);

        $import40DaysOld = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(40),
        ]);

        $import20DaysOld = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('ofx_imports', ['id' => $import40DaysOld->id]);
        $this->assertDatabaseHas('ofx_imports', ['id' => $import20DaysOld->id]);
    }

    public function test_handles_missing_files_gracefully(): void
    {
        $expiredImport = OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(100),
            'file_path' => 'ofx_imports/nonexistent.ofx.gz',
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('ofx_imports', ['id' => $expiredImport->id]);
    }

    public function test_shows_no_expired_imports_message_when_none_found(): void
    {
        OfxImport::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $this->artisan('ofx:cleanup --force')
            ->assertSuccessful()
            ->expectsOutput('No expired OFX imports found.');
    }
}
