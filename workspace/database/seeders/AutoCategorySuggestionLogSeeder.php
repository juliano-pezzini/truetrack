<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AutoCategorySuggestionLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class AutoCategorySuggestionLogSeeder extends Seeder
{
    /**
     * Seed auto-categorization suggestion logs for demo users.
     */
    public function run(): void
    {
        $sources = ['rule_exact', 'rule_fuzzy', 'learned_keyword', 'manual_suggestion'];
        $actions = [null, 'accepted', 'rejected', 'ignored'];

        User::query()->each(function (User $user) use ($sources, $actions): void {
            $transactions = Transaction::query()
                ->where('user_id', $user->id)
                ->limit(15)
                ->get();

            if ($transactions->isEmpty()) {
                $transactions = Transaction::factory(15)->forUser($user)->create();
            }

            $categories = Category::query()->where('user_id', $user->id)->get();

            if ($categories->isEmpty()) {
                return;
            }

            foreach ($transactions as $transaction) {
                $action = $actions[array_rand($actions)];

                AutoCategorySuggestionLog::create([
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'suggested_category_id' => $categories->random()->id,
                    'confidence_score' => random_int(45, 95),
                    'matched_keywords' => $transaction->description
                        ? array_slice(explode(' ', strtolower($transaction->description)), 0, 3)
                        : [],
                    'source' => $sources[array_rand($sources)],
                    'user_action' => $action,
                    'suggested_at' => now()->subDays(random_int(1, 30)),
                    'action_at' => $action ? now()->subDays(random_int(0, 7)) : null,
                ]);
            }
        });
    }
}
