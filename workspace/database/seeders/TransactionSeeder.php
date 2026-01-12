<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $accounts = Account::where('user_id', $user->id)->get();
            $tags = Tag::where('user_id', $user->id)->get();

            if ($accounts->isEmpty()) {
                continue;
            }

            // Create 50 transactions per user
            Transaction::factory()
                ->count(50)
                ->forUser($user)
                ->create()
                ->each(function ($transaction) use ($tags) {
                    // Attach 0-3 random tags to each transaction
                    if ($tags->isNotEmpty()) {
                        $transaction->tags()->attach(
                            $tags->random(min(rand(0, 3), $tags->count()))->pluck('id')
                        );
                    }
                });
        }
    }
}
