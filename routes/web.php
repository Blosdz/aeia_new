<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('payments',function(){
    return Inertia::render('payments/AdminPayments');
})->middleware(['auth','verified'])->name('payments');

Route::get('profiles',function(){
    return Inertia::render('profiles/ProfileTableAdmin');
})->middleware(['auth','verified'])->name('profiles');

Route::get('sales',function(){
    return Inertia::render('sales/SalesAdmin');
})->middleware(['auth','verified'])->name('sales');

Route::get('funds',function(){
    return Inertia::render('funds/FundsAdmin');
})->middleware(['auth','verified'])->name('funds');



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
