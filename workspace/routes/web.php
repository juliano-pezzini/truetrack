<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Account management
    Route::resource('accounts', App\Http\Controllers\AccountController::class);

    // Category management
    Route::resource('categories', App\Http\Controllers\CategoryController::class);

    // Tag management
    Route::resource('tags', App\Http\Controllers\TagController::class);

    // Transaction management
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
});

require __DIR__.'/auth.php';
