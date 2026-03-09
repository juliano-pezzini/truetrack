<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Permission;
use App\Models\Reconciliation;
use App\Models\Role;
use App\Models\User;
use App\Models\XlsxImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();

        // Grant permissions
        $permission = Permission::create([
            'name' => 'manage-reconciliations',
            'description' => 'Can manage reconciliations',
        ]);
        $role = Role::create(['name' => 'user']);
        $role->permissions()->attach($permission->id);
        $this->user->roles()->attach($role->id);
    }

    public function test_index_returns_mixed_imports_sorted_by_date(): void
    {
        // Create imports with different timestamps
        $ofx1 = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'created_at' => now()->subDays(2),
        ]);

        $xlsx1 = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'created_at' => now()->subDay(),
        ]);

        $ofx2 = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $ofx2->id)
            ->assertJsonPath('data.0.type', 'ofx')
            ->assertJsonPath('data.1.id', $xlsx1->id)
            ->assertJsonPath('data.1.type', 'xlsx')
            ->assertJsonPath('data.2.id', $ofx1->id)
            ->assertJsonPath('data.2.type', 'ofx');
    }

    public function test_index_filters_by_account_id(): void
    {
        $account2 = Account::factory()->for($this->user)->create();

        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $account2->id,
        ]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?filter[account_id]='.$this->account->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_filters_by_status(): void
    {
        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pending',
        ]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?filter[status]=completed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_type(): void
    {
        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        XlsxImport::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        // Filter for OFX only
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?filter[type]=ofx');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Filter for XLSX only
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?filter[type]=xlsx');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_pagination_works_correctly(): void
    {
        // Create 25 imports (should paginate)
        OfxImport::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        XlsxImport::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 10);

        // Test page 2
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_show_returns_ofx_import(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/imports/ofx/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.type', 'ofx')
            ->assertJsonPath('data.filename', $import->filename);
    }

    public function test_show_returns_xlsx_import(): void
    {
        $import = XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/imports/xlsx/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.type', 'xlsx')
            ->assertJsonPath('data.filename', $import->filename)
            ->assertJsonStructure([
                'data' => [
                    'skipped_count',
                    'duplicate_count',
                    'has_errors',
                ],
            ]);
    }

    public function test_show_returns_404_for_invalid_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports/invalid/1');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_nonexistent_import(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports/ofx/9999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_access_others_imports(): void
    {
        $otherUser = User::factory()->create();
        $import = OfxImport::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => Account::factory()->for($otherUser)->create()->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/imports/ofx/{$import->id}");

        $response->assertStatus(404); // Should not find import from other user
    }

    public function test_active_count_returns_correct_number(): void
    {
        // Create various imports
        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pending',
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'processing',
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports/active-count');

        $response->assertStatus(200)
            ->assertJsonPath('active_count', 4) // 2 pending + 1 processing + 1 pending xlsx
            ->assertJsonPath('ofx_active_count', 3)
            ->assertJsonPath('xlsx_active_count', 1);
    }

    public function test_stats_returns_aggregated_data(): void
    {
        // Create various imports with different statuses
        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pending',
        ]);

        XlsxImport::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        XlsxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports/stats');

        $response->assertStatus(200)
            ->assertJsonPath('total_imports', 7)
            ->assertJsonPath('by_status.completed', 5)
            ->assertJsonPath('by_status.pending', 1)
            ->assertJsonPath('by_status.failed', 1)
            ->assertJsonPath('by_type.ofx.total', 3)
            ->assertJsonPath('by_type.xlsx.total', 4)
            ->assertJsonPath('by_type.ofx.by_status.completed', 2)
            ->assertJsonPath('by_type.xlsx.by_status.completed', 3);
    }

    public function test_index_includes_account_relationship(): void
    {
        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'account' => [
                            'id',
                            'name',
                            'type',
                        ],
                    ],
                ],
            ]);
    }

    public function test_index_includes_reconciliation_when_present(): void
    {
        $reconciliation = Reconciliation::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'reconciliation_id' => $reconciliation->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'reconciliation' => [
                            'id',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    public function test_index_respects_per_page_limit(): void
    {
        OfxImport::factory()->count(100)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        // Request more than max allowed (50)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/imports?per_page=100');

        $response->assertStatus(200)
            ->assertJsonCount(50, 'data'); // Should be capped at 50
    }

    public function test_unauthenticated_user_cannot_access_imports(): void
    {
        $response = $this->getJson('/api/v1/imports');

        $response->assertStatus(401);
    }
}
