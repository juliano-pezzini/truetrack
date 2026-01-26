<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Jobs\ProcessOfxImport;
use App\Models\Account;
use App\Models\OfxImport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfxImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->for($this->user)->create();

        // Grant manage-reconciliations permission
        $permission = Permission::create([
            'name' => 'manage-reconciliations',
            'description' => 'Can manage reconciliations',
        ]);
        $role = Role::create(['name' => 'user']);
        $role->permissions()->attach($permission->id);
        $this->user->roles()->attach($role->id);

        // Create settings
        Setting::create([
            'key' => 'max_concurrent_imports_per_user',
            'value' => '5',
            'type' => 'integer',
            'category' => 'import',
        ]);
    }

    public function test_can_list_ofx_imports(): void
    {
        OfxImport::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_filters_by_user(): void
    {
        $otherUser = User::factory()->create();

        OfxImport::factory()->count(2)->create(['user_id' => $this->user->id]);
        OfxImport::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_filters_by_account(): void
    {
        $otherAccount = Account::factory()->for($this->user)->create();

        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);
        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $otherAccount->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports?filter[account_id]='.$this->account->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_upload_ofx_file(): void
    {
        $file = UploadedFile::fake()->create('statement.ofx', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'import' => ['id', 'filename', 'status'],
            ]);

        $this->assertDatabaseHas('ofx_imports', [
            'filename' => 'statement.ofx',
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessOfxImport::class);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->create('statement.ofx', 100);

        $response = $this->postJson('/api/v1/ofx-imports', [
            'file' => $file,
            'account_id' => $this->account->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();

        $file = UploadedFile::fake()->create('statement.ofx', 100);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_upload_validates_file_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_account_required(): void
    {
        $file = UploadedFile::fake()->create('statement.ofx', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id']);
    }

    public function test_upload_rejects_duplicate_without_force(): void
    {
        $fileContent = 'OFXHEADER:100';
        $fileHash = hash('sha256', $fileContent);

        // Create existing import with same hash
        OfxImport::factory()->create([
            'file_hash' => $fileHash,
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        // Create a file with known content to get predictable hash
        $ofxContent = '<?xml version="1.0" encoding="UTF-8"?><OFX></OFX>';
        $file = UploadedFile::fake()->createWithContent('statement.ofx', $ofxContent);

        // Calculate the hash that will be generated
        $expectedHash = hash('sha256', $ofxContent);

        // Update the existing import with this hash
        OfxImport::where('account_id', $this->account->id)
            ->update(['file_hash' => $expectedHash]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'This file has already been imported for this account.']);
    }

    public function test_upload_checks_concurrency_limit(): void
    {
        Setting::where('key', 'max_concurrent_imports_per_user')->update(['value' => '2']);

        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
        ]);

        $file = UploadedFile::fake()->create('statement.ofx', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ofx-imports', [
                'file' => $file,
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(429)
            ->assertJson(['message' => 'Maximum concurrent imports reached. Please wait for existing imports to complete.']);
    }

    public function test_can_show_import_with_progress(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'processed_count' => 50,
            'total_count' => 100,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports/'.$import->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('progress', 50);
    }

    public function test_cannot_show_other_users_import(): void
    {
        $otherUser = User::factory()->create();
        $import = OfxImport::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports/'.$import->id);

        $response->assertStatus(404);
    }

    public function test_can_cancel_pending_import(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/ofx-imports/'.$import->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Import cancelled successfully.']);

        $this->assertDatabaseHas('ofx_imports', [
            'id' => $import->id,
            'status' => 'failed',
            'error_message' => 'Cancelled by user',
        ]);
    }

    public function test_cannot_cancel_completed_import(): void
    {
        $import = OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/ofx-imports/'.$import->id);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete a completed import.']);
    }

    public function test_can_get_active_count(): void
    {
        OfxImport::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
        ]);
        OfxImport::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ofx-imports/active-count');

        $response->assertStatus(200)
            ->assertJson(['active_count' => 2]);
    }
}
