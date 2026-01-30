# Phase 5: Analytics Dashboard

**Goal**: Provide comprehensive financial insights and projections

**Status**: Planned

**Estimated Effort**: 2-3 weeks

---

## Overview

Build a dashboard with financial analytics, charts, spending analysis, and alerts. Provides users with real-time insights into their financial status.

---

## Deliverables

### Business Logic
- `ReportingService` for calculations
- Period summary calculations
- Cash flow projections
- Spending analysis by category
- Investment return tracking
- Alert generation

### API
- Reporting endpoints with filtering
- Summary calculations
- Alert endpoints

### Web
- Dashboard page (Inertia)
- React chart components

### Database
- No new tables required
- Uses transactions, accounts, categories

### Testing
- Extensive ReportingService tests
- Jest tests for chart components
- Integration tests for full workflows

---

## Business Logic

### ReportingService

```php
class ReportingService
{
    // Calculate period profit/loss
    public function calculatePeriodSummary(
        int $userId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Returns: total_income, total_expenses, net_profit_loss
    }
    
    // Generate monthly cash flow projection
    public function generateCashFlowProjection(
        int $userId,
        int $months = 6
    ): array {
        // Returns: monthly projections with confidence scores
    }
    
    // Analyze spending by category
    public function analyzeSpendingByCategory(
        int $userId,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        // Returns: spending per category with percentages
    }
    
    // Calculate investment returns
    public function calculateInvestmentReturns(
        int $userId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Returns: ROI, gains, losses, performance metrics
    }
    
    // Generate alerts for accounts at risk
    public function generateAlerts(int $userId): Collection {
        // Returns: list of alert objects with severity levels
    }
    
    // Calculate budget variance
    public function calculateBudgetVariance(
        int $userId,
        Carbon $month
    ): array {
        // Returns: actual vs projected spending
    }
}
```

---

## API Endpoints

### Reports Endpoints

**GET /api/v1/reports/period-summary**
```
Query Parameters:
  - start_date - Start date (required)
  - end_date - End date (required)
  - account_ids[] - Optional account filter

Response:
{
  "data": {
    "period": "2026-01-01 to 2026-01-31",
    "total_income": 5000.00,
    "total_expenses": 3500.00,
    "net_income": 1500.00,
    "accounts": [
      {
        "id": 1,
        "name": "Chase Checking",
        "income": 5000.00,
        "expenses": 3500.00,
        "net": 1500.00
      }
    ]
  }
}
```

**GET /api/v1/reports/spending-by-category**
```
Query Parameters:
  - start_date - Start date (required)
  - end_date - End date (required)

Response:
{
  "data": [
    {
      "category_id": 1,
      "category_name": "Groceries",
      "amount": 500.00,
      "percentage": 14.3,
      "transaction_count": 12,
      "trend": "up"
    }
  ]
}
```

**GET /api/v1/reports/cash-flow-projection**
```
Query Parameters:
  - months - Number of months to project (default: 6, max: 24)

Response:
{
  "data": [
    {
      "month": "2026-02",
      "projected_income": 5000.00,
      "projected_expenses": 3500.00,
      "projected_balance": 1500.00,
      "confidence": 0.85
    }
  ]
}
```

**GET /api/v1/reports/investment-returns**
```
Query Parameters:
  - start_date - Start date
  - end_date - End date
  - account_ids[] - Filter to specific accounts

Response:
{
  "data": {
    "period": "2026-01-01 to 2026-01-31",
    "total_invested": 10000.00,
    "current_value": 10500.00,
    "gains": 500.00,
    "roi_percentage": 5.0,
    "investments": [
      {
        "account_id": 5,
        "account_name": "Investment Account",
        "invested": 10000.00,
        "current_value": 10500.00,
        "roi": 5.0
      }
    ]
  }
}
```

**GET /api/v1/reports/alerts**
```
Response:
{
  "data": [
    {
      "id": 1,
      "type": "account_at_risk",
      "severity": "high",
      "account_id": 2,
      "account_name": "Credit Card",
      "message": "Account balance is critically low",
      "recommendation": "Increase payment towards credit card"
    }
  ]
}
```

### Monthly Balance History

**GET /api/v1/accounts/{id}/balance-history**
```
Query Parameters:
  - months - Number of months back (default: 12, max: 36)

Response:
{
  "data": {
    "account_id": 1,
    "account_name": "Chase Checking",
    "history": [
      {
        "year": 2026,
        "month": 1,
        "closing_balance": 1050.00,
        "change": 50.00,
        "percentage_change": 4.8
      }
    ]
  }
}
```

---

## React Components

### Dashboard Page
- Main dashboard layout
- Quick summary cards (income, expenses, net)
- Chart display (delegated to sub-components)
- Alerts panel
- Recent transactions list
- Quick action buttons

### PeriodSummaryCard Component
```jsx
export default function PeriodSummaryCard({ summary, onDateRangeChange })
```

**Features:**
- Display total income, expenses, net income
- Show period selector (This month, Last month, Last 3 months, Custom)
- Color-coded: Income (green), Expenses (red), Net (blue)
- Comparison to previous period (up/down indicator)

### CashFlowChart Component
```jsx
export default function CashFlowChart({ projections })
```

**Features:**
- Line chart showing income, expenses, balance trend
- Historical data + projected future
- Interactive tooltips with actual vs projected
- Color-coded lines
- Uses Recharts library

### SpendingByCategoryChart Component
```jsx
export default function SpendingByCategoryChart({ data })
```

**Features:**
- Pie chart or bar chart showing spending per category
- Click category to filter transactions
- Show percentages
- Hover tooltips with amounts
- Color-coded per category

### AlertsPanel Component
```jsx
export default function AlertsPanel({ alerts })
```

**Features:**
- List of alerts with severity indicators (high, medium, low)
- Alert types: Low balance, Credit card payment due, Budget exceeded
- Dismiss alerts
- Click to view related account
- Different icons per alert type

### InvestmentSummaryCard Component
```jsx
export default function InvestmentSummaryCard({ summary })
```

**Features:**
- Display ROI percentage
- Show gains/losses
- Display invested amount vs current value
- Color-coded gains (green) / losses (red)
- Breakdown by investment account

### BalanceHistoryChart Component
```jsx
export default function BalanceHistoryChart({ account, months })
```

**Features:**
- Area chart showing balance over time
- Display monthly closing balances
- Show balance trend
- Interactive tooltips with exact values
- Month selector (3, 6, 12, 24 months)

### TransactionsTrendChart Component
```jsx
export default function TransactionsTrendChart({ data })
```

**Features:**
- Line chart showing transaction count or amount over time
- Toggle between transaction count and total amount
- By account or by category
- Show spikes and trends

### BudgetVarianceCard Component
```jsx
export default function BudgetVarianceCard({ budget, actual })
```

**Features:**
- Show budgeted vs actual spending
- Progress bars per category
- Color-coded: Over budget (red), On track (green), Under budget (blue)
- Show remaining budget
- Click to see detailed breakdown

---

## Form Requests

None - this phase is read-only (reporting only)

---

## Testing Strategy

### PHPUnit Tests

**Service Tests** (`tests/Unit/Services/ReportingService/`):
```
- test_calculate_period_summary()
- test_period_summary_with_multiple_accounts()
- test_period_summary_zero_transactions()
- test_analyze_spending_by_category()
- test_spending_percentages_sum_to_100()
- test_analyze_spending_no_expenses()
- test_generate_cash_flow_projection()
- test_projection_respects_historical_trends()
- test_calculate_investment_returns()
- test_roi_calculation_accuracy()
- test_generate_alerts_for_low_balance()
- test_generate_alerts_for_credit_card_payment()
- test_generate_alerts_empty_when_no_risk()
- test_budget_variance_calculation()
```

**Feature Tests** (`tests/Feature/Api/Reports/`):
```
- test_user_can_get_period_summary()
- test_period_summary_requires_date_range()
- test_period_summary_filters_by_account()
- test_user_can_get_spending_by_category()
- test_user_can_get_cash_flow_projection()
- test_projection_limit_respected()
- test_user_can_get_investment_returns()
- test_user_can_get_alerts()
- test_user_can_get_balance_history()
- test_balance_history_respects_month_limit()
```

**Integration Tests** (`tests/Integration/`):
```
- test_full_dashboard_workflow()
- test_alerts_generated_correctly_for_accounts()
- test_reports_consistency_with_transactions()
```

### Jest Tests
```
- test_period_summary_card_renders()
- test_period_summary_card_changes_period()
- test_cash_flow_chart_renders_with_data()
- test_spending_by_category_chart_renders()
- test_alert_panel_displays_alerts()
- test_alert_panel_dismisses_alert()
- test_investment_summary_calculates_roi()
- test_balance_history_chart_renders()
- test_transactions_trend_chart_updates()
```

### Coverage Targets
- ReportingService: 90%+
- Controllers: 80%+
- React components: 70%+
- Overall: 80%+

---

## User Capabilities

After Phase 5 completion, users will be able to:

✅ **Financial Analysis**
- View period summary (income, expenses, net)
- Analyze spending by category with percentages
- See spending trends over time
- Compare periods (this month vs last month)

✅ **Projections**
- View cash flow projections for next 6-24 months
- Understand confidence levels in projections
- Plan based on historical patterns

✅ **Investment Tracking**
- Track investment account returns
- Calculate ROI on investments
- Compare investment performance

✅ **Alerts & Monitoring**
- Receive alerts for account at risk (low balance)
- Get credit card payment reminders
- Identify budget overages
- Set up custom alerts

✅ **Dashboard**
- View comprehensive financial dashboard
- See quick summary cards
- Access all reports and charts
- View recent transactions
- Quick navigation to key features

---

## Quality Gates

All PRs must pass:
- ✅ PHPUnit tests (all tests pass, including ReportingService)
- ✅ Jest tests (all tests pass)
- ✅ Laravel Pint (no style violations)
- ✅ PHPStan Level 5+ (no errors)
- ✅ Coverage 80%+ overall, 90%+ for ReportingService

---

## Notes

- Uses Recharts for charting (React component library)
- All calculations based on existing transactions
- No new data models required
- Projections use historical trends (moving average)
- Alerts are calculated on-demand, not stored
- All dates use user's timezone

---

## Previous Phase

[Phase 4: Transactions with Double-Entry Accounting](PHASE_04_TRANSACTIONS.md)

## Next Phase

[Phase 6: Bank and Credit Card Reconciliation with Statement Import](PHASE_06_STATEMENT_IMPORT.md)

---

**Created**: January 30, 2026
**Last Updated**: January 30, 2026
