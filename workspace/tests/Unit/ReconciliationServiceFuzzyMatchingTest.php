<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceFuzzyMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected ReconciliationService $service;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReconciliationService();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();

        // Set Levenshtein threshold to 20%
        Setting::create([
            'key' => 'levenshtein_distance_threshold_percent',
            'value' => '20',
            'type' => 'integer',
            'category' => 'matching',
        ]);
    }

    public function test_exact_match_returns_100_percent_confidence(): void
    {
        $date = Carbon::parse('2026-01-15');

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $date,
            'description' => 'Coffee Shop Purchase',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $date,
            'Coffee Shop Purchase'
        );

        $this->assertCount(1, $matches);
        $this->assertEquals(100, $matches[0]['confidence']);
        $this->assertStringContainsString('Exact match', $matches[0]['match_reason']);
    }

    public function test_strong_match_with_similar_description(): void
    {
        $date = Carbon::parse('2026-01-15');

        // Create transaction with very similar description (one character difference)
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $date,
            'description' => 'Coffee Shop',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $date,
            'Coffee Shops'
        );

        // "Coffee Shop" vs "Coffee Shops" - 1 char difference out of 12 chars = ~8% difference
        // This should pass the 20% threshold
        $this->assertCount(1, $matches);
        $this->assertEquals(75, $matches[0]['confidence']);
        $this->assertStringContainsString('Strong match', $matches[0]['match_reason']);
    }

    public function test_weak_match_with_date_difference(): void
    {
        $transactionDate = Carbon::parse('2026-01-10');
        $searchDate = Carbon::parse('2026-01-15'); // 5 days difference

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $transactionDate,
            'description' => 'Some purchase',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $searchDate,
            'Different description'
        );

        $this->assertCount(1, $matches);
        $this->assertEquals(50, $matches[0]['confidence']);
        $this->assertStringContainsString('Weak match', $matches[0]['match_reason']);
    }

    public function test_no_match_outside_date_range(): void
    {
        $transactionDate = Carbon::parse('2026-01-01');
        $searchDate = Carbon::parse('2026-01-15'); // 14 days difference (outside ±7 days)

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $transactionDate,
            'description' => 'Old purchase',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $searchDate,
            'Old purchase'
        );

        $this->assertCount(0, $matches);
    }

    public function test_no_match_for_different_amount(): void
    {
        $date = Carbon::parse('2026-01-15');

        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $date,
            'description' => 'Purchase',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            200.00, // Different amount
            $date,
            'Purchase'
        );

        $this->assertCount(0, $matches);
    }

    public function test_matches_sorted_by_confidence_then_date(): void
    {
        $searchDate = Carbon::parse('2026-01-15');

        // Exact match
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $searchDate,
            'description' => 'Purchase',
        ]);

        // Strong match - one character difference (within 20% threshold)
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $searchDate->copy()->addDay(),
            'description' => 'Purchases',
        ]);

        // Weak match - different description, further date
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $searchDate->copy()->addDays(5),
            'description' => 'Something else',
        ]);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $searchDate,
            'Purchase'
        );

        $this->assertCount(3, $matches);
        $this->assertEquals(100, $matches[0]['confidence']);
        $this->assertEquals(75, $matches[1]['confidence']);
        $this->assertEquals(50, $matches[2]['confidence']);
    }

    public function test_excludes_completed_reconciliations(): void
    {
        $date = Carbon::parse('2026-01-15');

        $transaction = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'amount' => 100.00,
            'transaction_date' => $date,
            'description' => 'Test Transaction',
        ]);

        // Create completed reconciliation with transaction
        $reconciliation = \App\Models\Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);
        $reconciliation->transactions()->attach($transaction->id);

        $matches = $this->service->findMatchingTransactionsWithConfidence(
            $this->account->id,
            100.00,
            $date,
            'Test Transaction'
        );

        $this->assertCount(0, $matches);
    }

    public function test_levenshtein_distance_calculation(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateLevenshteinDistance');
        $method->setAccessible(true);

        $distance = $method->invoke($this->service, 'kitten', 'sitting');

        $this->assertEquals(3, $distance);
    }

    public function test_levenshtein_distance_case_insensitive(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateLevenshteinDistance');
        $method->setAccessible(true);

        $distance = $method->invoke($this->service, 'TEST', 'test');

        $this->assertEquals(0, $distance);
    }

    public function test_custom_levenshtein_for_long_strings(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('customLevenshtein');
        $method->setAccessible(true);

        $str1 = str_repeat('a', 300);
        $str2 = str_repeat('a', 299).'b';

        $distance = $method->invoke($this->service, $str1, $str2);

        $this->assertEquals(1, $distance);
    }
}
