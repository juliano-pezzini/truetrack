<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\AccountResource;
use App\Http\Resources\ImportResource;
use App\Http\Resources\ReconciliationResource;
use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\XlsxImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImportResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
    }

    public function test_transforms_ofx_import_correctly(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'filename' => 'test.ofx',
            'status' => 'completed',
            'processed_count' => 10,
            'total_count' => 10,
        ]);

        $import->load('account');

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals($import->id, $array['id']);
        $this->assertEquals('ofx', $array['type']);
        $this->assertEquals('test.ofx', $array['filename']);
        $this->assertEquals('completed', $array['status']);
        $this->assertEquals(100, $array['progress']);
        $this->assertEquals(10, $array['processed_count']);
        $this->assertEquals(10, $array['total_count']);
        $this->assertArrayHasKey('matched_count', $array);
        $this->assertArrayNotHasKey('skipped_count', $array);
        $this->assertArrayNotHasKey('duplicate_count', $array);
    }

    public function test_transforms_xlsx_import_correctly(): void
    {
        $import = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'filename' => 'test.xlsx',
            'status' => 'completed',
            'processed_count' => 8,
            'total_count' => 10,
            'skipped_count' => 1,
            'duplicate_count' => 1,
        ]);

        $import->load('account');

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals($import->id, $array['id']);
        $this->assertEquals('xlsx', $array['type']);
        $this->assertEquals('test.xlsx', $array['filename']);
        $this->assertEquals('completed', $array['status']);
        $this->assertEquals(80, $array['progress']);
        $this->assertEquals(8, $array['processed_count']);
        $this->assertEquals(10, $array['total_count']);
        $this->assertEquals(1, $array['skipped_count']);
        $this->assertEquals(1, $array['duplicate_count']);
        $this->assertArrayHasKey('has_errors', $array);
        $this->assertArrayNotHasKey('matched_count', $array);
    }

    public function test_includes_type_field(): void
    {
        $ofxImport = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $xlsxImport = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $ofxResource = new ImportResource($ofxImport);
        $xlsxResource = new ImportResource($xlsxImport);

        $ofxArray = $ofxResource->resolve(Request::create('/'));
        $xlsxArray = $xlsxResource->resolve(Request::create('/'));

        $this->assertEquals('ofx', $ofxArray['type']);
        $this->assertEquals('xlsx', $xlsxArray['type']);
    }

    public function test_includes_related_account(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $import->load('account');

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('account', $array);
        $this->assertInstanceOf(AccountResource::class, $array['account']);
        $accountData = $array['account']->resolve(Request::create('/'));
        $this->assertEquals($this->account->id, $accountData['id']);
        $this->assertEquals($this->account->name, $accountData['name']);
    }

    public function test_includes_related_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => $reconciliation->id,
        ]);

        $import->load('account', 'reconciliation');

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('reconciliation', $array);
        $this->assertInstanceOf(ReconciliationResource::class, $array['reconciliation']);
        $reconciliationData = $array['reconciliation']->resolve(Request::create('/'));
        $this->assertEquals($reconciliation->id, $reconciliationData['id']);
    }

    public function test_calculates_progress_correctly(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'processed_count' => 7,
            'total_count' => 10,
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(70, $array['progress']);
    }

    public function test_handles_zero_total_count(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'processed_count' => 0,
            'total_count' => 0,
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(0, $array['progress']);
    }

    public function test_throws_exception_for_invalid_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ImportResource accepts only OfxImport or XlsxImport models');

        $invalidModel = $this->user; // User model is not valid

        $resource = new ImportResource($invalidModel);
        $resource->resolve(Request::create('/'));
    }

    public function test_includes_matched_count_for_ofx_with_reconciliation(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        // Create some transactions for the reconciliation
        $reconciliation->transactions()->attach([
            \App\Models\Transaction::factory()->create([
                'account_id' => $this->account->id,
                'user_id' => $this->user->id,
            ])->id,
            \App\Models\Transaction::factory()->create([
                'account_id' => $this->account->id,
                'user_id' => $this->user->id,
            ])->id,
        ]);

        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => $reconciliation->id,
        ]);

        $import->load([
            'reconciliation' => fn ($query) => $query->withCount('transactions'),
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('matched_count', $array);
        $this->assertEquals(2, $array['matched_count']);
    }

    public function test_matched_count_is_zero_when_no_reconciliation(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => null,
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('matched_count', $array);
        $this->assertEquals(0, $array['matched_count']);
    }

    public function test_has_errors_true_when_error_report_exists(): void
    {
        $import = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'error_report_path' => 'errors/report.csv',
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertTrue($array['has_errors']);
    }

    public function test_has_errors_false_when_no_error_report(): void
    {
        $import = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'error_report_path' => null,
        ]);

        $resource = new ImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertFalse($array['has_errors']);
    }
}
