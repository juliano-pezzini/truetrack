<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportingService $reportingService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportingService = app(ReportingService::class);
        $this->user = User::factory()->create();
    }

    public function test_calculate_period_profit_loss(): void
    {
        // Create income category
        $incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::REVENUE,
            'name' => 'Salary',
        ]);

        // Create expense category
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
            'name' => 'Groceries',
        ]);

        // Create account
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'initial_balance' => 1000.00,
        ]);

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Create income transaction
        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'type' => TransactionType::CREDIT,
            'amount' => 3000.00,
            'transaction_date' => $startDate->copy()->addDays(5),
        ]);

        // Create expense transactions
        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'type' => TransactionType::DEBIT,
            'amount' => 500.00,
            'transaction_date' => $startDate->copy()->addDays(10),
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'type' => TransactionType::DEBIT,
            'amount' => 300.00,
            'transaction_date' => $startDate->copy()->addDays(15),
        ]);

        // Calculate profit/loss
        $result = $this->reportingService->calculatePeriodProfitLoss(
            $startDate,
            $endDate,
            $this->user->id
        );

        $this->assertEquals(3000.00, $result['revenue']);
        $this->assertEquals(800.00, $result['expenses']);
        $this->assertEquals(2200.00, $result['profit_loss']);
    }

    public function test_generate_cash_flow_projection(): void
    {
        // Create account with balance
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'initial_balance' => 2000.00,
        ]);

        // Create monthly balance snapshot
        AccountBalance::create([
            'account_id' => $account->id,
            'year' => Carbon::now()->year,
            'month' => Carbon::now()->month,
            'closing_balance' => 2500.00,
        ]);

        // Create recent transactions for averaging
        $incomeCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::REVENUE,
        ]);

        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
        ]);

        // Create 3 months of transactions
        for ($i = 1; $i <= 3; $i++) {
            $month = Carbon::now()->subMonths($i);

            Transaction::factory()->create([
                'account_id' => $account->id,
                'category_id' => $incomeCategory->id,
                'type' => TransactionType::CREDIT,
                'amount' => 3000.00,
                'transaction_date' => $month,
            ]);

            Transaction::factory()->create([
                'account_id' => $account->id,
                'category_id' => $expenseCategory->id,
                'type' => TransactionType::DEBIT,
                'amount' => 2000.00,
                'transaction_date' => $month,
            ]);
        }

        // Generate projection
        $result = $this->reportingService->generateCashFlowProjection(
            3,
            $this->user->id
        );

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('month', $result[0]);
        $this->assertArrayHasKey('projected_income', $result[0]);
        $this->assertArrayHasKey('projected_expenses', $result[0]);
        $this->assertArrayHasKey('net_cash_flow', $result[0]);

        // Check that projections contain expected average values
        $this->assertEquals(3000.00, $result[0]['projected_income']);
        $this->assertEquals(2000.00, $result[0]['projected_expenses']);
        $this->assertEquals(1000.00, $result[0]['net_cash_flow']);
    }

    public function test_get_spending_by_category(): void
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create multiple expense categories
        $groceries = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
            'name' => 'Groceries',
        ]);

        $utilities = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
            'name' => 'Utilities',
        ]);

        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Create transactions in different categories
        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $groceries->id,
            'type' => TransactionType::DEBIT,
            'amount' => 500.00,
            'transaction_date' => $startDate->copy()->addDays(5),
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $groceries->id,
            'type' => TransactionType::DEBIT,
            'amount' => 300.00,
            'transaction_date' => $startDate->copy()->addDays(10),
        ]);

        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $utilities->id,
            'type' => TransactionType::DEBIT,
            'amount' => 200.00,
            'transaction_date' => $startDate->copy()->addDays(15),
        ]);

        $result = $this->reportingService->getSpendingByCategory(
            $startDate,
            $endDate,
            $this->user->id
        );

        $this->assertCount(2, $result);

        $groceriesSpending = $result->firstWhere('category_name', 'Groceries');
        $this->assertEquals(800.00, $groceriesSpending['total_spent']);
        $this->assertEquals(80.0, $groceriesSpending['percentage']);

        $utilitiesSpending = $result->firstWhere('category_name', 'Utilities');
        $this->assertEquals(200.00, $utilitiesSpending['total_spent']);
        $this->assertEquals(20.0, $utilitiesSpending['percentage']);
    }

    public function test_calculate_investment_returns(): void
    {
        $startDate = Carbon::now()->subMonths(1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Create investment account with initial balance (created before start date)
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'name' => 'Investment Account',
            'initial_balance' => 10000.00,
            'is_active' => true,
            'created_at' => $startDate->copy()->subDays(10),
            'updated_at' => $startDate->copy()->subDays(10),
        ]);

        // Create a revenue category for gains
        $gainCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::REVENUE,
            'name' => 'Investment Gains',
        ]);

        // Add investment gains transaction DURING the test period
        Transaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $gainCategory->id,
            'type' => TransactionType::CREDIT,
            'amount' => 1500.00,
            'transaction_date' => $startDate->copy()->addDays(5),
        ]);

        $result = $this->reportingService->calculateInvestmentReturns(
            $startDate,
            $endDate,
            $this->user->id
        );

        $this->assertIsArray($result);
        // Initial value is the balance at start date = initial_balance
        $this->assertEquals(10000.00, $result['initial_value']);
        // Current value includes the transaction
        $this->assertEquals(11500.00, $result['current_value']);
        $this->assertEquals(1500.00, $result['return_amount']);
        $this->assertEquals(15.0, $result['return_percentage']);
    }

    public function test_get_accounts_at_risk(): void
    {
        // Create account with negative balance through transactions
        $riskAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'name' => 'Risk Account',
            'initial_balance' => 100.00,
            'is_active' => true,
        ]);

        // Create expense category
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
        ]);

        // Create transaction that makes the balance negative
        Transaction::factory()->create([
            'account_id' => $riskAccount->id,
            'category_id' => $expenseCategory->id,
            'type' => TransactionType::DEBIT,
            'amount' => 150.00, // More than initial balance
            'transaction_date' => Carbon::now(),
        ]);

        // Create account with positive balance
        $safeAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'name' => 'Safe Account',
            'initial_balance' => 1000.00,
        ]);

        $result = $this->reportingService->getAccountsAtRisk($this->user->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Risk Account', $result[0]['account_name']);
        $this->assertEquals(-50.00, $result[0]['balance']);
    }

    public function test_get_credit_card_alerts(): void
    {
        // Create bank account with sufficient funds
        $bankAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'initial_balance' => 2000.00,
            'is_active' => true,
        ]);

        // Create credit card with debt through transactions
        $creditCard = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::CREDIT_CARD,
            'name' => 'Credit Card',
            'initial_balance' => 0.00,
            'is_active' => true,
        ]);

        // Create expense category
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
        ]);

        // Create debit transaction on credit card (purchases)
        Transaction::factory()->create([
            'account_id' => $creditCard->id,
            'category_id' => $expenseCategory->id,
            'type' => TransactionType::DEBIT,
            'amount' => 1500.00,
            'transaction_date' => Carbon::now(),
        ]);

        $result = $this->reportingService->getCreditCardAlerts($this->user->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Credit Card', $result[0]['account_name']);
        $this->assertEquals(-1500.00, $result[0]['balance']);
        $this->assertEquals(1500.00, $result[0]['amount_owed']);
        $this->assertEquals(2000.00, $result[0]['available_funds']);
        $this->assertTrue($result[0]['can_pay_full']);
    }

    public function test_credit_card_alert_insufficient_funds(): void
    {
        // Create bank account with insufficient funds
        $bankAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::BANK,
            'initial_balance' => 500.00,
            'is_active' => true,
        ]);

        // Create credit card with larger debt through transactions
        $creditCard = Account::factory()->create([
            'user_id' => $this->user->id,
            'type' => AccountType::CREDIT_CARD,
            'initial_balance' => 0.00,
            'is_active' => true,
        ]);

        // Create expense category
        $expenseCategory = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => CategoryType::EXPENSE,
        ]);

        // Create debit transaction for credit card purchases
        Transaction::factory()->create([
            'account_id' => $creditCard->id,
            'category_id' => $expenseCategory->id,
            'type' => TransactionType::DEBIT,
            'amount' => 1500.00,
            'transaction_date' => Carbon::now(),
        ]);

        $result = $this->reportingService->getCreditCardAlerts($this->user->id);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]['can_pay_full']);
    }
}
