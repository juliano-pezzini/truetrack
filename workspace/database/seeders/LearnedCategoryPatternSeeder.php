<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearnedCategoryPatternSeeder extends Seeder
{
    /**
     * Seed learned category patterns for demo users.
     */
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $categories = $this->ensureCategories($user);

            $patterns = [
                ['keyword' => 'uber', 'category' => $categories['transportation']],
                ['keyword' => 'lyft', 'category' => $categories['transportation']],
                ['keyword' => 'starbucks', 'category' => $categories['dining']],
                ['keyword' => 'chipotle', 'category' => $categories['dining']],
                ['keyword' => 'target', 'category' => $categories['shopping']],
                ['keyword' => 'walmart', 'category' => $categories['shopping']],
                ['keyword' => 'wholefoods', 'category' => $categories['groceries']],
                ['keyword' => 'costco', 'category' => $categories['groceries']],
            ];

            foreach ($patterns as $pattern) {
                $occurrence = random_int(3, 12);
                $confidence = min(95, 50 + ($occurrence * 5));

                LearnedCategoryPattern::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'keyword' => $pattern['keyword'],
                        'category_id' => $pattern['category']->id,
                    ],
                    [
                        'occurrence_count' => $occurrence,
                        'confidence_score' => $confidence,
                        'first_learned_at' => now()->subDays(random_int(30, 180)),
                        'last_matched_at' => now()->subDays(random_int(1, 30)),
                        'is_active' => true,
                    ]
                );
            }
        });
    }

    /**
     * Ensure baseline categories exist for the user.
     *
     * @return array<string, Category>
     */
    private function ensureCategories(User $user): array
    {
        return [
            'shopping' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Shopping', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'General shopping expenses']
            ),
            'groceries' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Groceries', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'Food and household items']
            ),
            'transportation' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Transportation', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'Rideshare and transit']
            ),
            'dining' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Dining', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'Restaurants and cafes']
            ),
        ];
    }
}
