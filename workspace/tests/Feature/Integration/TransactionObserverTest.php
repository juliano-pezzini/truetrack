<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\AutoCategoryRule;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test observer auto-applies category when rule matches.
     */
    public function test_observer_auto_applies_rule_match(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
            'priority' => 10,
            'is_active' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
            'description' => 'Amazon Marketplace Purchase',
        ]);

        $transaction->refresh();

        $this->assertEquals($category->id, $transaction->category_id);
    }

    /**
     * Test observer skips auto-apply when description is empty.
     */
    public function test_observer_skips_without_description(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        AutoCategoryRule::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'pattern' => 'amazon',
            'priority' => 10,
            'is_active' => true,
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
            'description' => '',
        ]);

        $transaction->refresh();

        $this->assertNull($transaction->category_id);
    }
}
