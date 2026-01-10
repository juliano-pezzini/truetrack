<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create common tags
        $tags = [
            [
                'name' => 'Essential',
                'color' => '#EF4444', // Red
            ],
            [
                'name' => 'Entertainment',
                'color' => '#8B5CF6', // Violet
            ],
            [
                'name' => 'Investment',
                'color' => '#10B981', // Green
            ],
            [
                'name' => 'Business',
                'color' => '#3B82F6', // Blue
            ],
            [
                'name' => 'Personal',
                'color' => '#EC4899', // Pink
            ],
            [
                'name' => 'Recurring',
                'color' => '#F59E0B', // Amber
            ],
            [
                'name' => 'One-time',
                'color' => '#14B8A6', // Teal
            ],
            [
                'name' => 'Tax-deductible',
                'color' => '#F97316', // Orange
            ],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
