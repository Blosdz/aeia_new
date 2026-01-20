<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\CoverageController;
use App\Http\Controllers\Client\DocumentController;
use App\Http\Controllers\Client\CreditCardController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Http\Controllers\Client\RewardController;
use App\Http\Controllers\Client\BeneficiaryController;

/**
 * Client Routes
 * 
 * Prefix: /clients
 * Middleware: auth, verified, client (para verificar que sea cliente)
 */

Route::middleware(['auth', 'verified', 'is_client'])->prefix('clients')->group(function () {
    
    // Dashboard de cliente
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('clients.dashboard');
    
    // Perfil del cliente
    Route::get('profile', [ProfileController::class, 'show'])
        ->name('clients.profile');
    Route::put('profile', [ProfileController::class, 'update'])
        ->name('clients.profile.update');
    
    // Pagos
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])
            ->name('clients.payments');
        
        Route::get('select', [PaymentController::class, 'selectPlan'])
            ->middleware('profile.verified')
            ->name('clients.payments.select');

        Route::get('select/{id}', [PaymentController::class, 'selectPlanDetail'])
            ->middleware('profile.verified')
            ->name('clients.payments.select.detail');
        
        Route::post('/', [PaymentController::class, 'store'])
            ->name('clients.payments.store');

        Route::post('/confirm', [PaymentController::class, 'confirm'])
            ->name('clients.payments.confirm');

        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('clients.payments.show');
    });
    
    // Suscripciones
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('clients.subscriptions');
        
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])
            ->name('clients.subscriptions.show');
    });
    
    // Documentos
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])
            ->name('clients.documents');
        
        Route::post('/', [DocumentController::class, 'store'])
            ->name('clients.documents.store');
        
        Route::delete('/{document}', [DocumentController::class, 'destroy'])
            ->name('clients.documents.destroy');
    });
    
    // Cobertura
    Route::prefix('coverage')->group(function () {
        Route::get('/', [CoverageController::class, 'index'])
            ->name('clients.coverage');
        
        Route::get('select', [CoverageController::class, 'selectCoverage'])
            ->name('clients.coverage.select');
        
        Route::get('select/{id}', [CoverageController::class, 'selectCoverageDetail'])
            ->name('clients.coverage.select.detail');
        
        Route::post('/', [CoverageController::class, 'store'])
            ->name('clients.coverage.store');
        
        Route::get('/{coverage}', [CoverageController::class, 'show'])
            ->name('clients.coverage.show');
    });
    
    // Tarjetas de crédito
    Route::prefix('credit-cards')->group(function () {
        Route::get('/', [CreditCardController::class, 'index'])
            ->name('clients.credit_cards.index');
        
        Route::post('/', [CreditCardController::class, 'store'])
            ->name('clients.credit_cards.store');
        
        Route::put('/{creditCard}/default', [CreditCardController::class, 'setDefault'])
            ->name('clients.credit_cards.set_default');
        
        Route::delete('/{creditCard}', [CreditCardController::class, 'destroy'])
            ->name('clients.credit_cards.destroy');
    });
    
    // Recompensas / Reintegros
    Route::prefix('rewards')->group(function () {
        Route::get('/', [RewardController::class, 'index'])
            ->name('clients.rewards');

        Route::get('/{reward}', [RewardController::class, 'show'])
            ->name('clients.rewards.show');
    });

    // Beneficiarios
    Route::prefix('beneficiaries')->group(function () {
        Route::post('/', [BeneficiaryController::class, 'store'])
            ->name('clients.beneficiaries.store');

        Route::delete('/{beneficiary}', [BeneficiaryController::class, 'destroy'])
            ->name('clients.beneficiaries.destroy');
    });

});
