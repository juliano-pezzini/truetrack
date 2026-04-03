<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\OfxImportResource;
use App\Http\Resources\ReconciliationResource;
use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OfxImportResourceTest extends TestCase
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

    public function test_transforms_ofx_import_with_all_fields(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'filename' => 'statement.ofx',
            'status' => 'completed',
            'processed_count' => 15,
            'total_count' => 20,
            'error_message' => null,
        ]);

        $import->load('account');

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals($import->id, $array['id']);
        $this->assertEquals('statement.ofx', $array['filename']);
        $this->assertEquals('completed', $array['status']);
        $this->assertEquals(20, $array['total_count']);
        $this->assertEquals(15, $array['processed_count']);
        $this->assertNull($array['error_message']);
        $this->assertArrayHasKey('account', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('progress_percentage', $array);
        $this->assertArrayHasKey('matched_count', $array);
    }

    public function test_calculates_progress_percentage(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'processed_count' => 5,
            'total_count' => 10,
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(50, $array['progress_percentage']);
    }

    public function test_progress_percentage_is_zero_when_total_count_is_zero(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'processed_count' => 0,
            'total_count' => 0,
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(0, $array['progress_percentage']);
    }

    public function test_includes_matched_count_when_reconciliation_exists(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        // Create transactions and attach to reconciliation
        $transaction1 = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $transaction2 = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $reconciliation->transactions()->attach([$transaction1->id, $transaction2->id]);

        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => $reconciliation->id,
        ]);

        $import->load([
            'reconciliation' => fn ($query) => $query->withCount('transactions'),
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(2, $array['matched_count']);
    }

    public function test_matched_count_is_zero_when_no_reconciliation(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => null,
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals(0, $array['matched_count']);
    }

    public function test_loads_account_relationship(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $import->load('account');

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('account', $array);
        $this->assertIsArray($array['account']);
        $this->assertEquals($this->account->id, $array['account']['id']);
        $this->assertEquals($this->account->name, $array['account']['name']);
        $this->assertEquals($this->account->type, $array['account']['type']);
    }

    public function test_loads_reconciliation_relationship(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => $reconciliation->id,
        ]);

        $import->load('reconciliation');

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertArrayHasKey('reconciliation', $array);
        $this->assertInstanceOf(ReconciliationResource::class, $array['reconciliation']);
        $reconciliationData = $array['reconciliation']->resolve(Request::create('/'));
        $this->assertEquals($reconciliation->id, $reconciliationData['id']);
    }

    public function test_formats_timestamps_as_iso8601(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        // Check that timestamps are formatted as ISO8601 strings
        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $array['created_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $array['updated_at']);
    }

    public function test_includes_error_message_when_failed(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'failed',
            'error_message' => 'Invalid OFX format',
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        $this->assertEquals('failed', $array['status']);
        $this->assertEquals('Invalid OFX format', $array['error_message']);
    }

    public function test_rounds_progress_percentage_to_two_decimals(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'processed_count' => 7,
            'total_count' => 9,
        ]);

        $resource = new OfxImportResource($import);
        $array = $resource->resolve(Request::create('/'));

        // 7/9 = 77.777... should round to 77.78
        $this->assertEquals(77.78, $array['progress_percentage']);
    }
}
