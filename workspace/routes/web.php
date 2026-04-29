<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Account management
    Route::delete('/accounts/bulk-delete', [App\Http\Controllers\AccountController::class, 'bulkDestroy'])
        ->name('accounts.bulk-destroy');
    Route::resource('accounts', App\Http\Controllers\AccountController::class);

    // Category management
    Route::delete('/categories/bulk-delete', [App\Http\Controllers\CategoryController::class, 'bulkDestroy'])
        ->name('categories.bulk-destroy');
    Route::resource('categories', App\Http\Controllers\CategoryController::class);

    // Tag management
    Route::delete('/tags/bulk-delete', [App\Http\Controllers\TagController::class, 'bulkDestroy'])
        ->name('tags.bulk-destroy');
    Route::resource('tags', App\Http\Controllers\TagController::class);

    // Transaction management
    Route::delete('/transactions/bulk-delete', [App\Http\Controllers\TransactionController::class, 'bulkDestroy'])
        ->name('transactions.bulk-destroy');
    Route::resource('transactions', App\Http\Controllers\TransactionController::class);

    // Reconciliation management
    Route::resource('reconciliations', App\Http\Controllers\ReconciliationController::class);

    // Reconciliation-specific actions
    Route::prefix('reconciliations')->name('reconciliations.')->group(function () {
        Route::post('/{reconciliation}/transactions', [App\Http\Controllers\ReconciliationController::class, 'addTransaction'])
            ->name('add-transaction');
        Route::delete('/{reconciliation}/transactions/{transactionId}', [App\Http\Controllers\ReconciliationController::class, 'removeTransaction'])
            ->name('remove-transaction');
        Route::post('/{reconciliation}/complete', [App\Http\Controllers\ReconciliationController::class, 'complete'])
            ->name('complete');
    });

    // Credit card closure
    Route::get('/credit-card-closure', [App\Http\Controllers\ReconciliationController::class, 'creditCardClosureForm'])
        ->name('credit-card-closure.form');
    Route::post('/credit-card-closure', [App\Http\Controllers\ReconciliationController::class, 'creditCardClosure'])
        ->name('credit-card-closure');

    // Unified Import management (OFX + XLSX/CSV)
    Route::get('/imports', [App\Http\Controllers\ImportController::class, 'index'])
        ->name('imports.index');

    // Auto-Category Rules management
    Route::get('/auto-category-rules', [App\Http\Controllers\AutoCategoryRuleController::class, 'index'])
        ->name('auto-category-rules.index');

    // Learned Patterns management
    Route::get('/learned-patterns', [App\Http\Controllers\LearnedPatternController::class, 'index'])
        ->name('learned-patterns.index');
});

require __DIR__.'/auth.php';
