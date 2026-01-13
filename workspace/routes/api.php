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
    });
});
