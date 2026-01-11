<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please seed users first.');

            return;
        }

        // Revenue categories
        $revenueCategories = [
            ['name' => 'Salary', 'description' => 'Monthly salary income'],
            ['name' => 'Freelance', 'description' => 'Freelance work and contract jobs'],
            ['name' => 'Investments', 'description' => 'Investment returns and dividends'],
            ['name' => 'Business', 'description' => 'Business income'],
            ['name' => 'Other Income', 'description' => 'Other revenue sources'],
        ];

        foreach ($revenueCategories as $categoryData) {
            $category = Category::create([
                'user_id' => $user->id,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'type' => CategoryType::REVENUE,
                'is_active' => true,
            ]);

            // Add subcategories for specific categories
            if ($categoryData['name'] === 'Investments') {
                Category::create([
                    'user_id' => $user->id,
                    'parent_id' => $category->id,
                    'name' => 'Stocks',
                    'description' => 'Stock market dividends and capital gains',
                    'type' => CategoryType::REVENUE,
                    'is_active' => true,
                ]);

                Category::create([
                    'user_id' => $user->id,
                    'parent_id' => $category->id,
                    'name' => 'Real Estate',
                    'description' => 'Rental income and property sales',
                    'type' => CategoryType::REVENUE,
                    'is_active' => true,
                ]);
            }
        }

        // Expense categories
        $expenseCategories = [
            ['name' => 'Housing', 'description' => 'Rent, mortgage, and utilities', 'subs' => ['Rent', 'Utilities', 'Maintenance']],
            ['name' => 'Transportation', 'description' => 'Vehicle and public transport costs', 'subs' => ['Fuel', 'Public Transport', 'Parking']],
            ['name' => 'Food & Dining', 'description' => 'Groceries and restaurants', 'subs' => ['Groceries', 'Restaurants', 'Coffee Shops']],
            ['name' => 'Healthcare', 'description' => 'Medical expenses and insurance', 'subs' => ['Medical', 'Pharmacy', 'Insurance']],
            ['name' => 'Entertainment', 'description' => 'Entertainment and leisure', 'subs' => ['Movies', 'Sports', 'Hobbies']],
            ['name' => 'Shopping', 'description' => 'Clothing and personal items', 'subs' => ['Clothing', 'Electronics', 'Books']],
            ['name' => 'Bills & Utilities', 'description' => 'Recurring bills and subscriptions', 'subs' => ['Internet', 'Phone', 'Subscriptions']],
            ['name' => 'Education', 'description' => 'Education and training expenses', 'subs' => ['Tuition', 'Books', 'Courses']],
            ['name' => 'Personal Care', 'description' => 'Personal grooming and wellness', 'subs' => ['Haircut', 'Gym', 'Spa']],
            ['name' => 'Other Expenses', 'description' => 'Miscellaneous expenses', 'subs' => []],
        ];

        foreach ($expenseCategories as $categoryData) {
            $category = Category::create([
                'user_id' => $user->id,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'type' => CategoryType::EXPENSE,
                'is_active' => true,
            ]);

            // Add subcategories
            foreach ($categoryData['subs'] as $subName) {
                Category::create([
                    'user_id' => $user->id,
                    'parent_id' => $category->id,
                    'name' => $subName,
                    'description' => null,
                    'type' => CategoryType::EXPENSE,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
