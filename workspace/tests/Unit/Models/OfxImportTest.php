<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfxImportTest extends TestCase
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

    public function test_is_completed_returns_true_for_completed_status(): void
    {
        $import = OfxImport::factory()->create(['status' => 'completed']);

        $this->assertTrue($import->isCompleted());
    }

    public function test_is_completed_returns_false_for_other_statuses(): void
    {
        $import = OfxImport::factory()->create(['status' => 'pending']);

        $this->assertFalse($import->isCompleted());
    }

    public function test_is_failed_returns_true_for_failed_status(): void
    {
        $import = OfxImport::factory()->create(['status' => 'failed']);

        $this->assertTrue($import->isFailed());
    }

    public function test_is_processing_returns_true_for_processing_status(): void
    {
        $import = OfxImport::factory()->create(['status' => 'processing']);

        $this->assertTrue($import->isProcessing());
    }

    public function test_get_progress_percentage_calculates_correctly(): void
    {
        $import = OfxImport::factory()->create([
            'processed_count' => 50,
            'total_count' => 100,
        ]);

        $this->assertEquals(50.0, $import->getProgressPercentage());
    }

    public function test_get_progress_percentage_returns_zero_for_zero_total(): void
    {
        $import = OfxImport::factory()->create([
            'processed_count' => 0,
            'total_count' => 0,
        ]);

        $this->assertEquals(0.0, $import->getProgressPercentage());
    }

    public function test_get_progress_percentage_returns_hundred_when_complete(): void
    {
        $import = OfxImport::factory()->create([
            'processed_count' => 75,
            'total_count' => 75,
        ]);

        $this->assertEquals(100.0, $import->getProgressPercentage());
    }

    public function test_active_scope_includes_pending_and_processing(): void
    {
        OfxImport::factory()->create(['status' => 'pending']);
        OfxImport::factory()->create(['status' => 'processing']);
        OfxImport::factory()->create(['status' => 'completed']);
        OfxImport::factory()->create(['status' => 'failed']);

        $activeImports = OfxImport::active()->get();

        $this->assertCount(2, $activeImports);
    }

    public function test_for_user_scope_filters_by_user_id(): void
    {
        $otherUser = User::factory()->create();

        OfxImport::factory()->count(3)->create(['user_id' => $this->user->id]);
        OfxImport::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $userImports = OfxImport::forUser($this->user->id)->get();

        $this->assertCount(3, $userImports);
    }

    public function test_for_account_scope_filters_by_account_id(): void
    {
        $otherAccount = Account::factory()->for($this->user)->create();

        OfxImport::factory()->count(2)->create(['account_id' => $this->account->id]);
        OfxImport::factory()->count(3)->create(['account_id' => $otherAccount->id]);

        $accountImports = OfxImport::forAccount($this->account->id)->get();

        $this->assertCount(2, $accountImports);
    }

    public function test_belongs_to_account_relationship(): void
    {
        $import = OfxImport::factory()->create(['account_id' => $this->account->id]);

        $this->assertInstanceOf(Account::class, $import->account);
        $this->assertEquals($this->account->id, $import->account->id);
    }

    public function test_belongs_to_user_relationship(): void
    {
        $import = OfxImport::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(User::class, $import->user);
        $this->assertEquals($this->user->id, $import->user->id);
    }

    public function test_belongs_to_reconciliation_relationship(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $import = OfxImport::factory()->create([
            'reconciliation_id' => $reconciliation->id,
        ]);

        $this->assertInstanceOf(Reconciliation::class, $import->reconciliation);
        $this->assertEquals($reconciliation->id, $import->reconciliation->id);
    }
}
