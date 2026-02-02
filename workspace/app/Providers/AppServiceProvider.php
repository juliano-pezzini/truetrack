<?php

namespace App\Providers;

use App\Models\AutoCategoryRule;
use App\Models\LearnedCategoryPattern;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Policies\AutoCategoryRulePolicy;
use App\Policies\LearnedPatternPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register Transaction observer for auto-categorization
        Transaction::observe(TransactionObserver::class);

        // Register policies (explicit registration for non-standard naming)
        Gate::policy(AutoCategoryRule::class, AutoCategoryRulePolicy::class);
        Gate::policy(LearnedCategoryPattern::class, LearnedPatternPolicy::class);
    }
}
