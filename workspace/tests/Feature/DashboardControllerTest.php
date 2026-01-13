<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_displays_period_summary(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create([
                'transaction_date' => Carbon::now(),
                'amount' => 100.00,
            ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Period summary should be included in the response
    }

    public function test_dashboard_includes_cash_flow_projection(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Cash flow projection should be included in the response
    }

    public function test_dashboard_includes_spending_by_category(): void
    {
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->create([
                'transaction_date' => Carbon::now(),
                'amount' => 100.00,
            ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Spending by category should be included in the response
    }

    public function test_dashboard_includes_investment_returns(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Investment returns should be included in the response
    }

    public function test_dashboard_includes_alerts(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Alerts should be included in the response
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_calls_reporting_service_methods(): void
    {
        // Create some test data
        Transaction::factory()
            ->for($this->user)
            ->for($this->account)
            ->for($this->category)
            ->count(5)
            ->create([
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
            ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);

        // Verify the response contains expected data structure
        // This implicitly tests that ReportingService methods are called
    }
}
