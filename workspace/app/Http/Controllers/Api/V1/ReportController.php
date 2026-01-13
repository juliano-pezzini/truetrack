<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    /**
     * Get period profit/loss summary
     */
    public function periodSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $summary = $this->reportingService->calculatePeriodProfitLoss(
            $startDate,
            $endDate,
            $request->user()->id
        );

        return response()->json([
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Get cash flow projection
     */
    public function cashFlowProjection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'months_ahead' => 'sometimes|integer|min:1|max:24',
        ]);

        $monthsAhead = (int) ($validated['months_ahead'] ?? 6);

        $projections = $this->reportingService->generateCashFlowProjection(
            $monthsAhead,
            $request->user()->id
        );

        return response()->json([
            'data' => [
                'months_ahead' => $monthsAhead,
                'projections' => $projections,
            ],
        ]);
    }

    /**
     * Get spending by category
     */
    public function spendingByCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $spending = $this->reportingService->getSpendingByCategory(
            $startDate,
            $endDate,
            $request->user()->id
        );

        return response()->json([
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'categories' => $spending,
                'total_spent' => $spending->sum('total_spent'),
            ],
        ]);
    }

    /**
     * Get investment returns
     */
    public function investmentReturns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $returns = $this->reportingService->calculateInvestmentReturns(
            $startDate,
            $endDate,
            $request->user()->id
        );

        return response()->json([
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'returns' => $returns,
            ],
        ]);
    }

    /**
     * Get financial alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        $accountsAtRisk = $this->reportingService->getAccountsAtRisk(
            $request->user()->id
        );

        $creditCardAlerts = $this->reportingService->getCreditCardAlerts(
            $request->user()->id
        );

        return response()->json([
            'data' => [
                'accounts_at_risk' => $accountsAtRisk,
                'credit_card_alerts' => $creditCardAlerts,
                'total_alerts' => $accountsAtRisk->count() + $creditCardAlerts->count(),
            ],
        ]);
    }
}
