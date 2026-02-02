<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\AutoCategoryRule;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class AutoCategoryRuleSeeder extends Seeder
{
    /**
     * Seed auto-categorization rules for demo users.
     */
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $categories = $this->ensureCategories($user);

            $rules = [
                ['pattern' => 'amazon', 'category' => $categories['shopping'], 'priority' => 10],
                ['pattern' => 'whole foods', 'category' => $categories['groceries'], 'priority' => 20],
                ['pattern' => 'trader joe', 'category' => $categories['groceries'], 'priority' => 30],
                ['pattern' => 'netflix', 'category' => $categories['entertainment'], 'priority' => 40],
                ['pattern' => 'spotify', 'category' => $categories['entertainment'], 'priority' => 50],
                ['pattern' => 'electric', 'category' => $categories['utilities'], 'priority' => 60],
                ['pattern' => 'gas bill', 'category' => $categories['utilities'], 'priority' => 70],
                ['pattern' => 'salary', 'category' => $categories['salary'], 'priority' => 80],
            ];

            foreach ($rules as $rule) {
                AutoCategoryRule::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'pattern' => $rule['pattern'],
                    ],
                    [
                        'category_id' => $rule['category']->id,
                        'priority' => $rule['priority'],
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
            'entertainment' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Entertainment', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'Streaming and entertainment']
            ),
            'utilities' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Utilities', 'type' => CategoryType::EXPENSE->value],
                ['description' => 'Electricity, gas, water, and internet']
            ),
            'salary' => Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Salary', 'type' => CategoryType::REVENUE->value],
                ['description' => 'Paychecks and salary income']
            ),
        ];
    }
}
