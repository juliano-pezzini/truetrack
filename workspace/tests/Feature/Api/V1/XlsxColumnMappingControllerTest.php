<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use App\Models\XlsxColumnMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XlsxColumnMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_list_column_mappings(): void
    {
        XlsxColumnMapping::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/xlsx-column-mappings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'mapping_config',
                        'is_default',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_mappings_by_account(): void
    {
        $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);

        // Global mapping (null account_id)
        XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => null,
        ]);

        // Account-specific mapping
        XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        // Other account mapping
        XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/xlsx-column-mappings?filter[account_id]={$this->account->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Should return global + account-specific
    }

    public function test_can_create_column_mapping(): void
    {
        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-column-mappings', [
                'name' => 'My Mapping',
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
                'is_default' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'mapping_config',
                    'is_default',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('xlsx_column_mappings', [
            'user_id' => $this->user->id,
            'name' => 'My Mapping',
            'account_id' => $this->account->id,
            'is_default' => true,
        ]);
    }

    public function test_setting_default_unsets_other_defaults(): void
    {
        // Create existing default mapping
        $existingDefault = XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'is_default' => true,
        ]);

        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-column-mappings', [
                'name' => 'New Default',
                'account_id' => $this->account->id,
                'mapping_config' => $mappingConfig,
                'is_default' => true,
            ]);

        $response->assertStatus(201);

        // Check old default is no longer default
        $this->assertDatabaseHas('xlsx_column_mappings', [
            'id' => $existingDefault->id,
            'is_default' => false,
        ]);
    }

    public function test_can_update_column_mapping(): void
    {
        $mapping = XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/xlsx-column-mappings/{$mapping->id}", [
                'name' => 'Updated Name',
                'is_default' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Column mapping updated successfully.',
            ]);

        $this->assertDatabaseHas('xlsx_column_mappings', [
            'id' => $mapping->id,
            'name' => 'Updated Name',
            'is_default' => true,
        ]);
    }

    public function test_cannot_update_other_users_mapping(): void
    {
        $otherUser = User::factory()->create();
        $mapping = XlsxColumnMapping::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/xlsx-column-mappings/{$mapping->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_column_mapping(): void
    {
        $mapping = XlsxColumnMapping::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/xlsx-column-mappings/{$mapping->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('xlsx_column_mappings', [
            'id' => $mapping->id,
        ]);
    }

    public function test_cannot_delete_other_users_mapping(): void
    {
        $otherUser = User::factory()->create();
        $mapping = XlsxColumnMapping::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/xlsx-column-mappings/{$mapping->id}");

        $response->assertStatus(403);
    }

    public function test_create_mapping_requires_name(): void
    {
        $mappingConfig = [
            'date_column' => 'Date',
            'description_column' => 'Description',
            'amount_strategy' => 'single',
            'amount_column' => 'Amount',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/xlsx-column-mappings', [
                'mapping_config' => $mappingConfig,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_mapping_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/xlsx-column-mappings');

        $response->assertStatus(401);
    }
}
