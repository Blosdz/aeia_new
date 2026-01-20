<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RewardController;

/**
 * Admin Routes
 * 
 * Prefix: /admin
 * Middleware: auth, verified, admin (para verificar que sea admin)
 */

Route::middleware(['auth', 'verified', 'is_admin'])->prefix('admin')->group(function () {
    
    // Dashboard del admin
    Route::get('/', [DashboardController::class, 'index'])
        ->name('admin.dashboard');
    
    // Gestión de usuarios
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('admin.users.index');
        
        Route::get('/create', [UserController::class, 'create'])
            ->name('admin.users.create');
        
        Route::post('/', [UserController::class, 'store'])
            ->name('admin.users.store');
        
        Route::get('/{user}/edit', [UserController::class, 'edit'])
            ->name('admin.users.edit');
        
        Route::put('/{user}', [UserController::class, 'update'])
            ->name('admin.users.update');
        
        Route::delete('/{user}', [UserController::class, 'destroy'])
            ->name('admin.users.destroy');
        
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('admin.users.toggle_status');
    });
    
    // Gestión de clientes
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index'])
            ->name('admin.clients.index');
        
        Route::get('/{user}', [ClientController::class, 'show'])
            ->name('admin.clients.show');
        
        Route::patch('/{user}/verify', [ClientController::class, 'verify'])
            ->name('admin.clients.verify');
        
        Route::patch('/{user}/reject', [ClientController::class, 'reject'])
            ->name('admin.clients.reject');
        
        Route::patch('/{user}/reset-verification', [ClientController::class, 'resetVerification'])
            ->name('admin.clients.reset_verification');
    });

    // Gestión de pagos y suscripciones
    Route::prefix('payments')->group(function () {
        // Dashboard de pagos (DEBE ir antes de /{payment})
        Route::get('/dashboard', [PaymentController::class, 'dashboard'])
            ->name('admin.payments.dashboard');

        // Crear fondo - Formulario
        Route::get('/fund/create', [PaymentController::class, 'createFundForm'])
            ->name('admin.payments.create_fund_form');

        // Actualizar valor de fondo - Formulario
        Route::get('/fund/update-value', [PaymentController::class, 'updateFundValueForm'])
            ->name('admin.payments.update_fund_value_form');

        // Listar pagos
        Route::get('/', [PaymentController::class, 'index'])
            ->name('admin.payments.index');
        
        // Ver detalle del pago
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('admin.payments.show');
        
        // Validar pago
        Route::post('/{payment}/validate', [PaymentController::class, 'validate'])
            ->name('admin.payments.validate');
        
        // Rechazar pago
        Route::post('/{payment}/reject', [PaymentController::class, 'reject'])
            ->name('admin.payments.reject');
        
        // Crear fondo - POST
        Route::post('/fund/create', [PaymentController::class, 'createFund'])
            ->name('admin.payments.create_fund');
        
        // Asignar pagos a fondo
        Route::post('/fund/allocate', [PaymentController::class, 'allocateToFund'])
            ->name('admin.payments.allocate_to_fund');
        
        // Actualizar valor del fondo - POST
        Route::post('/fund/update-value', [PaymentController::class, 'updateFundValue'])
            ->name('admin.payments.update_fund_value');
        
        // Cerrar fondo y distribuir ganancias - POST
        Route::post('/fund/{fund}/close', [PaymentController::class, 'closeFund'])
            ->name('admin.payments.close_fund');
        
        // Obtener rewards del fondo - GET
        Route::get('/fund/{fund}/rewards', [PaymentController::class, 'getFundRewards'])
            ->name('admin.payments.fund_rewards');
    });
    
    // Recompensas / Rewards
    Route::prefix('rewards')->group(function () {
        Route::get('/', [RewardController::class, 'index'])
            ->name('admin.rewards');
        
        Route::get('/{reward}', [RewardController::class, 'show'])
            ->name('admin.rewards.show');
    });
    
});

