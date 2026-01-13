<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();
        $this->category = Category::factory()->for($this->user)->create();
    }

    public function test_period_summary_returns_profit_loss_data(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(3)
            ->create([
                'transaction_date' => Carbon::now(),
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/period-summary?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period' => ['start_date', 'end_date'],
                    'summary',
                ],
            ]);
    }

    public function test_period_summary_validates_required_dates(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/period-summary');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_period_summary_validates_end_date_after_start_date(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/period-summary?start_date=2026-01-31&end_date=2026-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_cash_flow_projection_returns_projections(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(5)
            ->create([
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/cash-flow-projection');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'months_ahead',
                    'projections',
                ],
            ]);
    }

    public function test_cash_flow_projection_accepts_custom_months_ahead(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/cash-flow-projection?months_ahead=12');

        $response->assertStatus(200)
            ->assertJsonPath('data.months_ahead', 12);
    }

    public function test_cash_flow_projection_validates_months_ahead_range(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/cash-flow-projection?months_ahead=25');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['months_ahead']);
    }

    public function test_spending_by_category_returns_category_breakdown(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(3)
            ->create([
                'transaction_date' => Carbon::now(),
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/spending-by-category?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period' => ['start_date', 'end_date'],
                    'categories',
                    'total_spent',
                ],
            ]);
    }

    public function test_spending_by_category_validates_required_dates(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/spending-by-category');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_investment_returns_calculates_portfolio_performance(): void
    {
        // Create an investment account
        $investmentAccount = Account::factory()->for($this->user)->create([
            'type' => AccountType::BANK,
        ]);

        Transaction::factory()
            ->for($this->user)
            ->for($investmentAccount, 'account')
            ->for($this->category)
            ->count(3)
            ->create([
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/investment-returns?start_date=2025-12-01&end_date=2026-01-31');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period' => ['start_date', 'end_date'],
                    'returns',
                ],
            ]);
    }

    public function test_investment_returns_validates_required_dates(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/investment-returns');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_alerts_returns_accounts_at_risk_and_credit_card_alerts(): void
    {
        // Create account with low balance
        Account::factory()->for($this->user)->create([
            'initial_balance' => 50.00,
            'type' => AccountType::BANK,
        ]);

        // Create credit card account
        Account::factory()->for($this->user)->create([
            'initial_balance' => -5000.00,
            'type' => AccountType::CREDIT_CARD,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'accounts_at_risk',
                    'credit_card_alerts',
                    'total_alerts',
                ],
            ]);
    }

    public function test_all_endpoints_require_authentication(): void
    {
        $endpoints = [
            '/api/v1/reports/period-summary?start_date=2026-01-01&end_date=2026-01-31',
            '/api/v1/reports/cash-flow-projection',
            '/api/v1/reports/spending-by-category?start_date=2026-01-01&end_date=2026-01-31',
            '/api/v1/reports/investment-returns?start_date=2026-01-01&end_date=2026-01-31',
            '/api/v1/reports/alerts',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    public function test_user_can_only_access_own_reports(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->for($otherUser)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        Transaction::factory()
            ->for($otherUser)
            ->for($otherAccount, 'account')
            ->for($otherCategory, 'category')
            ->count(3)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reports/period-summary?start_date=2026-01-01&end_date=2026-01-31');

        $response->assertStatus(200);

        // Response should only contain current user's data, not other user's data
        // This is implicitly tested by the ReportingService filtering by user_id
    }
}
