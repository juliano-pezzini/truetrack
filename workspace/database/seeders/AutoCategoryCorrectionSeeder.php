<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AutoCategoryCorrection;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class AutoCategoryCorrectionSeeder extends Seeder
{
    /**
     * Seed auto-categorization corrections for demo users.
     */
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $transactions = Transaction::query()
                ->where('user_id', $user->id)
                ->limit(10)
                ->get();

            if ($transactions->isEmpty()) {
                $transactions = Transaction::factory(10)->forUser($user)->create();
            }

            $categories = Category::query()->where('user_id', $user->id)->get();

            if ($categories->isEmpty()) {
                return;
            }

            foreach ($transactions->take(5) as $transaction) {
                $originalCategory = $transaction->category_id
                    ? $categories->firstWhere('id', $transaction->category_id)
                    : $categories->random();

                $newCategory = $categories->where('id', '!=', $originalCategory?->id)->random();

                AutoCategoryCorrection::create([
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'original_category_id' => $originalCategory?->id,
                    'corrected_category_id' => $newCategory->id,
                    'description_text' => (string) $transaction->description,
                    'correction_type' => 'auto_to_manual',
                    'confidence_at_correction' => random_int(40, 90),
                    'corrected_at' => now()->subDays(random_int(1, 30)),
                ]);
            }
        });
    }
}
