<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Support\SupportController;
use App\Http\Controllers\Support\UserValidationController;
use App\Http\Controllers\Support\PaymentValidationController;

/**
 * Support Routes
 * 
 * Prefix: /support
 * Middleware: auth, verified, support (para verificar que sea support)
 */

Route::middleware(['auth', 'verified', 'is_support'])->prefix('support')->group(function () {
    
    // Dashboard del support
    Route::get('/', [SupportController::class, 'dashboard'])
        ->name('support.dashboard');
    
    // Validación de usuarios
    Route::prefix('user-validation')->group(function () {
        Route::get('/', [UserValidationController::class, 'index'])
            ->name('support.user-validation.index');
        
        Route::get('/{profile}', [UserValidationController::class, 'show'])
            ->name('support.user-validation.show');
        
        Route::post('/{profile}/approve', [UserValidationController::class, 'approve'])
            ->name('support.user-validation.approve');
        
        Route::post('/{profile}/reject', [UserValidationController::class, 'reject'])
            ->name('support.user-validation.reject');
        
        Route::post('/{profile}/reset', [UserValidationController::class, 'reset'])
            ->name('support.user-validation.reset');
    });
    
    // Validación de pagos
    Route::prefix('payment-validation')->group(function () {
        Route::get('/', [PaymentValidationController::class, 'index'])
            ->name('support.payment-validation.index');
        
        Route::get('/{payment}', [PaymentValidationController::class, 'show'])
            ->name('support.payment-validation.show');
        
        Route::post('/{payment}/approve', [PaymentValidationController::class, 'approve'])
            ->name('support.payment-validation.approve');
        
        Route::post('/{payment}/reject', [PaymentValidationController::class, 'reject'])
            ->name('support.payment-validation.reject');
        
        Route::post('/{payment}/refund', [PaymentValidationController::class, 'refund'])
            ->name('support.payment-validation.refund');
    });
    
});