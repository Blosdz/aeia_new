<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    if ($user->roles()->where('name', 'admin')->exists()) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->roles()->where('name', 'support')->exists()) {
        return redirect()->route('support.dashboard');
    }
    if ($user->roles()->where('name', 'staff')->exists()) {
        return redirect()->route('staff.dashboard');
    }
    if ($user->roles()->where('name', 'client')->exists()) {
        return redirect()->route('clients.dashboard');
    }
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


Route::middleware(['auth','verified'])->get('/dashboardClient',[ClientController::class,'index'])->name('client.dashboard');

Route::get('profileClient',function(){
    return Inertia::render('');
})->middleware(['auth','verified'])->name('');
Route::get('planesClient',function(){
    return Inertia::render('');
})->middleware(['auth','verified'])->name(''); 
Route::get('coberturaClient',function(){
    return Inertia::render('');
})->middleware(['auth','verified'])->name(''); 
Route::get('contratosClient',function(){
    return Inertia::render('');
})->middleware(['auth','verified'])->name(''); 

require __DIR__.'/auth.php';
require __DIR__.'/clients.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/staff.php';
require __DIR__.'/support.php';
