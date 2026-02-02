<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\AutoCategoryRule;
use App\Models\Category;
use App\Models\LearnedCategoryPattern;
use App\Models\Reconciliation;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\XlsxColumnMapping;
use App\Models\XlsxImport;
use App\Observers\TransactionObserver;
use App\Policies\AccountPolicy;
use App\Policies\AutoCategoryRulePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\LearnedPatternPolicy;
use App\Policies\ReconciliationPolicy;
use App\Policies\TagPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\XlsxColumnMappingPolicy;
use App\Policies\XlsxImportPolicy;
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

        // Register policies (explicit registration required in Laravel 11+)
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(AutoCategoryRule::class, AutoCategoryRulePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(LearnedCategoryPattern::class, LearnedPatternPolicy::class);
        Gate::policy(Reconciliation::class, ReconciliationPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(XlsxColumnMapping::class, XlsxColumnMappingPolicy::class);
        Gate::policy(XlsxImport::class, XlsxImportPolicy::class);
    }
}
