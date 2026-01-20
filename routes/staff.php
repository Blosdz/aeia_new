<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\ProfileController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard principal
    Route::get('/staff/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');

    // Comisiones
    Route::get('/staff/commissions', [DashboardController::class, 'commissions'])->name('staff.commissions');

    // Referidos
    Route::get('/staff/referrals', [DashboardController::class, 'referrals'])->name('staff.referrals');

    // Perfil
    Route::get('/staff/profile', [ProfileController::class, 'edit'])->name('staff.profile.edit');
    Route::put('/staff/profile', [ProfileController::class, 'update'])->name('staff.profile.update');
});
