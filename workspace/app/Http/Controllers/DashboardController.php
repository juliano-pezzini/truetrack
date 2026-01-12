<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    /**
     * Display the dashboard
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        // Default to current month
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get all dashboard data
        $periodSummary = $this->reportingService->calculatePeriodProfitLoss(
            $startDate,
            $endDate,
            $userId
        );

        $cashFlowProjection = $this->reportingService->generateCashFlowProjection(
            6,
            $userId
        );

        $spendingByCategory = $this->reportingService->getSpendingByCategory(
            $startDate,
            $endDate,
            $userId
        );

        // Get last 3 months for investment returns
        $investmentStartDate = Carbon::now()->subMonths(3)->startOfMonth();
        $investmentReturns = $this->reportingService->calculateInvestmentReturns(
            $investmentStartDate,
            $endDate,
            $userId
        );

        $accountsAtRisk = $this->reportingService->getAccountsAtRisk($userId);
        $creditCardAlerts = $this->reportingService->getCreditCardAlerts($userId);

        return Inertia::render('Dashboard', [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'periodSummary' => $periodSummary,
            'cashFlowProjection' => $cashFlowProjection,
            'spendingByCategory' => $spendingByCategory,
            'investmentReturns' => $investmentReturns,
            'alerts' => [
                'accounts_at_risk' => $accountsAtRisk,
                'credit_card_alerts' => $creditCardAlerts,
                'total_count' => $accountsAtRisk->count() + $creditCardAlerts->count(),
            ],
        ]);
    }
}
