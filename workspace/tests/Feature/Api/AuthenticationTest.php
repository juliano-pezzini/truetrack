<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_prevents_duplicate_emails(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_users_can_login_via_api(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
                'message',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_valid_data(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_users_can_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_unauthenticated_users_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_users_can_logout_via_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();

        // For this test, we need to manually create tokens
        $token1 = $user->createToken('token-1')->plainTextToken;
        $token2 = $user->createToken('token-2')->plainTextToken;

        // Verify both tokens work initially
        $this->assertCount(2, $user->fresh()->tokens);

        // Logout with token1
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token1",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200);

        // Verify only 1 token remains in database
        $this->assertCount(1, $user->fresh()->tokens);

        // Verify the remaining token is token2 (token-2 name)
        $this->assertEquals('token-2', $user->fresh()->tokens->first()->name);

        // Token2 should still be valid
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token2",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/user');

        $response->assertStatus(200);
        $response->assertJson([
            'email' => $user->email,
        ]);
    }

    public function test_login_revokes_all_previous_tokens(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Create an existing token
        $oldToken = $user->createToken('old-token')->plainTextToken;

        // Login to get a new token (should revoke old token)
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $newToken = $response->json('data.token');

        // Old token should be invalid
        $response = $this->withHeaders([
            'Authorization' => "Bearer $oldToken",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/user');

        $response->assertStatus(401);

        // New token should be valid
        $response = $this->withHeaders([
            'Authorization' => "Bearer $newToken",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/user');

        $response->assertStatus(200);
    }
}
