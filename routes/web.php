<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CardPurchaseController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\ClassificationRuleController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\RecurrenceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('accounts', AccountController::class)
        ->except(['show', 'create', 'edit']);

    Route::resource('transactions', TransactionController::class)
        ->except(['show', 'create', 'edit']);

    Route::patch('transactions/{transaction}/category', [TransactionController::class, 'categorize'])
        ->name('transactions.categorize');

    Route::resource('classification-rules', ClassificationRuleController::class)
        ->except(['show', 'create', 'edit']);

    Route::get('inbox', InboxController::class)->name('inbox');

    Route::get('classification/suggest', [ClassificationController::class, 'suggest'])
        ->name('classification.suggest');

    Route::resource('credit-cards', CreditCardController::class)
        ->except(['create', 'edit']);

    Route::resource('card-purchases', CardPurchaseController::class)
        ->only(['store', 'destroy']);

    Route::resource('invoices', InvoiceController::class)
        ->only(['index']);

    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])
        ->name('invoices.pay');

    Route::post('invoices/{invoice}/close', [InvoiceController::class, 'close'])
        ->name('invoices.close');

    Route::resource('budgets', BudgetController::class)
        ->except(['show', 'create', 'edit']);

    Route::resource('goals', GoalController::class)
        ->except(['show', 'create', 'edit']);

    Route::post('goals/{goal}/deposit', [GoalController::class, 'deposit'])
        ->name('goals.deposit');
    Route::post('goals/{goal}/withdraw', [GoalController::class, 'withdraw'])
        ->name('goals.withdraw');

    Route::resource('recurrences', RecurrenceController::class)
        ->except(['show', 'create', 'edit']);

    Route::patch('recurrences/{recurrence}/toggle', [RecurrenceController::class, 'toggle'])
        ->name('recurrences.toggle');

    // Aliases for common action names
    foreach (['add', 'contribute', 'save', 'guardar', 'deposit_money', 'add_money', 'contribute_money'] as $action) {
        Route::post("goals/{goal}/{$action}", [GoalController::class, 'deposit'])
            ->name("goals.{$action}");
    }
    foreach (['redeem', 'rescue', 'resgatar', 'remove', 'withdraw_money', 'redeem_money'] as $action) {
        Route::post("goals/{goal}/{$action}", [GoalController::class, 'withdraw'])
            ->name("goals.{$action}");
    }
});

require __DIR__.'/settings.php';
