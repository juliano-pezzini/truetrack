<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| API routes are versioned and prefixed with /api/v1
| Authentication via Laravel Sanctum tokens
|
*/

Route::prefix('v1')->group(function () {
    // Public routes (no authentication required)
    Route::post('/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::get('/user', fn (Request $request) => $request->user());

        // Account management
        Route::apiResource('accounts', App\Http\Controllers\Api\V1\AccountController::class)
            ->names([
                'index' => 'api.accounts.index',
                'store' => 'api.accounts.store',
                'show' => 'api.accounts.show',
                'update' => 'api.accounts.update',
                'destroy' => 'api.accounts.destroy',
            ]);

        // Category management
        Route::apiResource('categories', App\Http\Controllers\Api\V1\CategoryController::class)
            ->names([
                'index' => 'api.categories.index',
                'store' => 'api.categories.store',
                'show' => 'api.categories.show',
                'update' => 'api.categories.update',
                'destroy' => 'api.categories.destroy',
            ]);

        // Tag management
        Route::apiResource('tags', App\Http\Controllers\Api\V1\TagController::class)
            ->names([
                'index' => 'api.tags.index',
                'store' => 'api.tags.store',
                'show' => 'api.tags.show',
                'update' => 'api.tags.update',
                'destroy' => 'api.tags.destroy',
            ]);

        // Transaction management
        Route::apiResource('transactions', App\Http\Controllers\Api\V1\TransactionController::class)
            ->names([
                'index' => 'api.transactions.index',
                'store' => 'api.transactions.store',
                'show' => 'api.transactions.show',
                'update' => 'api.transactions.update',
                'destroy' => 'api.transactions.destroy',
            ]);

        // Reconciliation management
        Route::apiResource('reconciliations', App\Http\Controllers\Api\V1\ReconciliationController::class)
            ->names([
                'index' => 'api.reconciliations.index',
                'store' => 'api.reconciliations.store',
                'show' => 'api.reconciliations.show',
                'update' => 'api.reconciliations.update',
                'destroy' => 'api.reconciliations.destroy',
            ]);

        // Reconciliation-specific actions
        Route::prefix('reconciliations')->name('api.reconciliations.')->group(function () {
            Route::post('/{reconciliation}/transactions', [App\Http\Controllers\Api\V1\ReconciliationController::class, 'addTransaction'])
                ->name('add-transaction');
            Route::delete('/{reconciliation}/transactions/{transactionId}', [App\Http\Controllers\Api\V1\ReconciliationController::class, 'removeTransaction'])
                ->name('remove-transaction');
            Route::post('/{reconciliation}/complete', [App\Http\Controllers\Api\V1\ReconciliationController::class, 'complete'])
                ->name('complete');
            Route::get('/{reconciliation}/suggested-transactions', [App\Http\Controllers\Api\V1\ReconciliationController::class, 'suggestedTransactions'])
                ->name('suggested-transactions');
        });

        // Credit card closure workflow
        Route::post('/credit-card-closure', [App\Http\Controllers\Api\V1\ReconciliationController::class, 'creditCardClosure'])
            ->name('api.credit-card-closure');

        // OFX Import management
        // OFX Import-specific actions (must be before resource routes)
        Route::get('/ofx-imports/active-count', [App\Http\Controllers\Api\V1\OfxImportController::class, 'activeCount'])
            ->name('api.ofx-imports.active-count');

        Route::apiResource('ofx-imports', App\Http\Controllers\Api\V1\OfxImportController::class)
            ->only(['index', 'store', 'show', 'destroy'])
            ->names([
                'index' => 'api.ofx-imports.index',
                'store' => 'api.ofx-imports.store',
                'show' => 'api.ofx-imports.show',
                'destroy' => 'api.ofx-imports.destroy',
            ]);

        // XLSX Import management
        // XLSX Import-specific actions (must be before resource routes)
        Route::post('/xlsx-imports/detect-columns', [App\Http\Controllers\Api\V1\XlsxImportController::class, 'detectColumns'])
            ->name('api.xlsx-imports.detect-columns');
        Route::post('/xlsx-imports/preview', [App\Http\Controllers\Api\V1\XlsxImportController::class, 'preview'])
            ->name('api.xlsx-imports.preview');
        Route::get('/xlsx-imports/template', [App\Http\Controllers\Api\V1\XlsxImportController::class, 'template'])
            ->name('api.xlsx-imports.template');
        Route::get('/xlsx-imports/{xlsxImport}/download', [App\Http\Controllers\Api\V1\XlsxImportController::class, 'download'])
            ->name('api.xlsx-imports.download');
        Route::get('/xlsx-imports/{xlsxImport}/error-report', [App\Http\Controllers\Api\V1\XlsxImportController::class, 'errorReport'])
            ->name('api.xlsx-imports.error-report');

        Route::apiResource('xlsx-imports', App\Http\Controllers\Api\V1\XlsxImportController::class)
            ->only(['index', 'store', 'show'])
            ->names([
                'index' => 'api.xlsx-imports.index',
                'store' => 'api.xlsx-imports.store',
                'show' => 'api.xlsx-imports.show',
            ]);

        // XLSX Column Mapping management
        Route::apiResource('xlsx-column-mappings', App\Http\Controllers\Api\V1\XlsxColumnMappingController::class)
            ->names([
                'index' => 'api.xlsx-column-mappings.index',
                'store' => 'api.xlsx-column-mappings.store',
                'update' => 'api.xlsx-column-mappings.update',
                'destroy' => 'api.xlsx-column-mappings.destroy',
            ]);

        // Reports and Analytics
        Route::prefix('reports')->name('api.reports.')->group(function () {
            Route::get('/period-summary', [App\Http\Controllers\Api\V1\ReportController::class, 'periodSummary'])
                ->name('period-summary');
            Route::get('/cash-flow-projection', [App\Http\Controllers\Api\V1\ReportController::class, 'cashFlowProjection'])
                ->name('cash-flow-projection');
            Route::get('/spending-by-category', [App\Http\Controllers\Api\V1\ReportController::class, 'spendingByCategory'])
                ->name('spending-by-category');
            Route::get('/investment-returns', [App\Http\Controllers\Api\V1\ReportController::class, 'investmentReturns'])
                ->name('investment-returns');
            Route::get('/alerts', [App\Http\Controllers\Api\V1\ReportController::class, 'alerts'])
                ->name('alerts');
        });

        // Auto-Categorization Rules
        Route::prefix('auto-category-rules')->name('api.auto-category-rules.')->group(function () {
            // Special routes MUST come before apiResource routes
            Route::post('/reorder', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'reorder'])
                ->name('reorder');
            Route::post('/test-coverage', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'testCoverage'])
                ->name('test-coverage');
            Route::get('/export', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'export'])
                ->name('export');
            Route::post('/import', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'import'])
                ->name('import');
        });

        // Auto-Category Rules CRUD (using apiResource for proper model binding)
        Route::apiResource('auto-category-rules', App\Http\Controllers\Api\V1\AutoCategoryRuleController::class)
            ->names([
                'index' => 'api.auto-category-rules.index',
                'store' => 'api.auto-category-rules.store',
                'show' => 'api.auto-category-rules.show',
                'update' => 'api.auto-category-rules.update',
                'destroy' => 'api.auto-category-rules.destroy',
            ]);

        // Auto-Category Rules custom actions (require model binding)
        Route::prefix('auto-category-rules')->name('api.auto-category-rules.')->group(function () {
            Route::post('/{auto_category_rule}/archive', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'archive'])
                ->name('archive');
            Route::post('/{auto_category_rule}/restore', [App\Http\Controllers\Api\V1\AutoCategoryRuleController::class, 'restore'])
                ->name('restore');
        });

        // Learned Category Patterns
        Route::prefix('learned-patterns')->name('api.learned-patterns.')->group(function () {
            // Special routes MUST come before apiResource routes
            Route::post('/clear-all', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'clearAll'])
                ->name('clear-all');
            Route::get('/statistics', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'statistics'])
                ->name('statistics');
            Route::get('/top-performers', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'topPerformers'])
                ->name('top-performers');
            Route::get('/underperforming', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'underperforming'])
                ->name('underperforming');
        });

        // Learned Patterns CRUD (using apiResource for proper model binding)
        Route::apiResource('learned-patterns', App\Http\Controllers\Api\V1\LearnedPatternController::class)
            ->names([
                'index' => 'api.learned-patterns.index',
                'show' => 'api.learned-patterns.show',
                'update' => 'api.learned-patterns.update',
                'destroy' => 'api.learned-patterns.destroy',
            ])
            ->only(['index', 'show', 'update', 'destroy']);

        // Learned Patterns custom actions
        Route::prefix('learned-patterns')->name('api.learned-patterns.')->group(function () {
            Route::post('/{learnedCategoryPattern}/toggle', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'toggle'])
                ->name('toggle');
            Route::post('/{learnedCategoryPattern}/convert', [App\Http\Controllers\Api\V1\LearnedPatternController::class, 'convert'])
                ->name('convert');
        });
    });
});
