<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Calculate period profit/loss (revenue - expenses)
     *
     * @return array{revenue: float, expenses: float, profit_loss: float}
     */
    public function calculatePeriodProfitLoss(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
    {
        $query = Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('category', function ($q) {
                $q->whereNull('deleted_at');
            });

        if ($userId) {
            $query->whereHas('account', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        // Calculate revenue (credits in revenue categories)
        $revenue = (float) $query->clone()
            ->where('type', TransactionType::CREDIT)
            ->whereHas('category', function ($q) {
                $q->where('type', CategoryType::REVENUE);
            })
            ->sum('amount');

        // Calculate expenses (debits in expense categories)
        $expenses = (float) $query->clone()
            ->where('type', TransactionType::DEBIT)
            ->whereHas('category', function ($q) {
                $q->where('type', CategoryType::EXPENSE);
            })
            ->sum('amount');

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit_loss' => $revenue - $expenses,
        ];
    }

    /**
     * Generate monthly cash flow projections
     *
     * @return Collection<int, array{month: string, month_name: string, projected_income: float, projected_expenses: float, net_cash_flow: float, projected_balance: float}>
     */
    public function generateCashFlowProjection(int $monthsAhead = 6, ?int $userId = null): Collection
    {
        $projections = collect();

        // Calculate average monthly income and expenses from the previous 3 full months
        $threeMonthsAgo = Carbon::now()->subMonths(3)->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $query = Transaction::query()
            ->whereBetween('transaction_date', [$threeMonthsAgo, $endOfLastMonth]);

        $monthsInRange = 3;

        if ($userId) {
            $query->whereHas('account', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        // Calculate monthly revenue totals, then average
        $revenueMonthlyTotals = $query->clone()
            ->where('type', TransactionType::CREDIT)
            ->whereHas('category', function ($q) {
                $q->where('type', CategoryType::REVENUE);
            })
            ->selectRaw("DATE_TRUNC('month', transaction_date) as month, SUM(amount) as total")
            ->groupBy('month')
            ->get();

        $avgRevenue = (float) ($revenueMonthlyTotals->sum('total') / $monthsInRange);

        // Calculate monthly expense totals, then average
        $expenseMonthlyTotals = $query->clone()
            ->where('type', TransactionType::DEBIT)
            ->whereHas('category', function ($q) {
                $q->where('type', CategoryType::EXPENSE);
            })
            ->selectRaw("DATE_TRUNC('month', transaction_date) as month, SUM(amount) as total")
            ->groupBy('month')
            ->get();

        $avgExpenses = (float) ($expenseMonthlyTotals->sum('total') / $monthsInRange);

        // Get current total balance across all active accounts
        $accountsQuery = Account::query()
            ->where('is_active', true);

        if ($userId) {
            $accountsQuery->where('user_id', $userId);
        }

        $accounts = $accountsQuery->get();
        $runningBalance = 0;
        $accountingService = app(AccountingService::class);

        foreach ($accounts as $account) {
            $runningBalance += $accountingService->calculateBalance($account, Carbon::now());
        }

        // Project forward
        for ($i = 1; $i <= $monthsAhead; $i++) {
            $month = Carbon::now()->addMonths($i);
            $netCashFlow = $avgRevenue - $avgExpenses;
            $runningBalance += $netCashFlow;

            $projections->push([
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('F Y'),
                'projected_income' => round($avgRevenue, 2),
                'projected_expenses' => round($avgExpenses, 2),
                'net_cash_flow' => round($netCashFlow, 2),
                'projected_balance' => round($runningBalance, 2),
            ]);
        }

        return $projections;
    }

    /**
     * Identify spending patterns by category
     *
     * @return Collection<int, array{category_id: int, category_name: string, total_spent: float, transaction_count: int, percentage: float}>
     */
    public function getSpendingByCategory(Carbon $startDate, Carbon $endDate, ?int $userId = null): Collection
    {
        $query = Transaction::query()
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('SUM(transactions.amount) as total_spent'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->where('transactions.type', TransactionType::DEBIT)
            ->where('categories.type', CategoryType::EXPENSE);

        if ($userId) {
            $query->whereHas('account', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $spending = $query
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_spent')
            ->get();

        // Calculate total for percentages
        $totalSpent = (float) $spending->sum('total_spent');

        /** @var Collection<int, array{category_id: int, category_name: string, total_spent: float, transaction_count: int, percentage: float}> */
        return $spending->map(function (object $item) use ($totalSpent): array {
            return [
                'category_id' => (int) $item->category_id,
                'category_name' => (string) $item->category_name,
                'total_spent' => (float) $item->total_spent,
                'transaction_count' => (int) $item->transaction_count,
                'percentage' => $totalSpent > 0 ? round(((float) $item->total_spent / $totalSpent) * 100, 2) : 0.0,
            ];
        });
    }

    /**
     * Track investment returns
     *
     * @return array{initial_value: float, current_value: float, return_amount: float, return_percentage: float}
     */
    public function calculateInvestmentReturns(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
    {
        // Get investment accounts (you may need to add an investment account type)
        $accountsQuery = Account::query()->where('is_active', true);

        if ($userId) {
            $accountsQuery->where('user_id', $userId);
        }

        $accounts = $accountsQuery->get();

        // Calculate initial value (balance at start date)
        $initialValue = 0;
        foreach ($accounts as $account) {
            $initialValue += app(AccountingService::class)->calculateBalance($account, $startDate);
        }

        // Calculate current value (balance at end date)
        $currentValue = 0;
        foreach ($accounts as $account) {
            $currentValue += app(AccountingService::class)->calculateBalance($account, $endDate);
        }

        $returnAmount = $currentValue - $initialValue;
        $returnPercentage = $initialValue > 0 ? ($returnAmount / $initialValue) * 100 : 0;

        return [
            'initial_value' => round($initialValue, 2),
            'current_value' => round($currentValue, 2),
            'return_amount' => round($returnAmount, 2),
            'return_percentage' => round($returnPercentage, 2),
        ];
    }

    /**
     * Alert on accounts at risk (negative balance for non-credit card accounts)
     *
     * @return Collection<int, array{account_id: int, account_name: string, balance: float, account_type: string}>
     */
    public function getAccountsAtRisk(?int $userId = null): Collection
    {
        $accountsQuery = Account::query()
            ->where('is_active', true)
            ->where('type', '!=', AccountType::CREDIT_CARD); // Credit cards are expected to be negative

        if ($userId) {
            $accountsQuery->where('user_id', $userId);
        }

        $accounts = $accountsQuery->get();
        $atRisk = collect();
        $accountingService = app(AccountingService::class);

        foreach ($accounts as $account) {
            $balance = $accountingService->calculateBalance($account, Carbon::now());

            if ($balance < 0) {
                $atRisk->push([
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'balance' => round($balance, 2),
                    'account_type' => $account->type->value,
                    'severity' => $this->calculateRiskSeverity($balance),
                ]);
            }
        }

        return $atRisk;
    }

    /**
     * Alert on credit card balances vs available funds
     *
     * @return Collection<int, array{account_id: int, account_name: string, balance: float, available_funds: float, can_pay_full: bool}>
     */
    public function getCreditCardAlerts(?int $userId = null): Collection
    {
        // Get credit card accounts
        $creditCardsQuery = Account::query()
            ->where('is_active', true)
            ->where('type', AccountType::CREDIT_CARD);

        if ($userId) {
            $creditCardsQuery->where('user_id', $userId);
        }

        $creditCards = $creditCardsQuery->get();

        // Get available funds from bank and wallet accounts
        $availableFundsQuery = Account::query()
            ->where('is_active', true)
            ->whereIn('type', [AccountType::BANK, AccountType::WALLET]);

        if ($userId) {
            $availableFundsQuery->where('user_id', $userId);
        }

        $availableAccounts = $availableFundsQuery->get();
        $totalAvailableFunds = 0;
        $accountingService = app(AccountingService::class);

        foreach ($availableAccounts as $account) {
            $balance = $accountingService->calculateBalance($account, Carbon::now());
            if ($balance > 0) {
                $totalAvailableFunds += $balance;
            }
        }

        $alerts = collect();
        $accountingService = app(AccountingService::class);

        foreach ($creditCards as $card) {
            $balance = $accountingService->calculateBalance($card, Carbon::now());
            $amountOwed = abs($balance); // Credit cards have negative balance

            if ($amountOwed > 0) {
                $alerts->push([
                    'account_id' => $card->id,
                    'account_name' => $card->name,
                    'balance' => round($balance, 2),
                    'amount_owed' => round($amountOwed, 2),
                    'available_funds' => round($totalAvailableFunds, 2),
                    'can_pay_full' => $totalAvailableFunds >= $amountOwed,
                    'payment_capacity_percentage' => $amountOwed > 0 ? round(($totalAvailableFunds / $amountOwed) * 100, 2) : 100,
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Calculate risk severity based on negative balance
     */
    private function calculateRiskSeverity(float $balance): string
    {
        $absBalance = abs($balance);

        if ($absBalance >= 1000) {
            return 'critical';
        } elseif ($absBalance >= 500) {
            return 'high';
        } elseif ($absBalance >= 100) {
            return 'medium';
        }

        return 'low';
    }
}
