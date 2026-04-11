<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::resource('users', \App\Http\Controllers\UserController::class);
Route::resource('roles', \App\Http\Controllers\RoleController::class);
Route::get('payments', [PaymentController::class, 'create'])->name('payments.create');
Route::post('payments/order', [PaymentController::class, 'order'])->name('payments.order');
Route::post('payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');
Route::post('payments/failure', [PaymentController::class, 'failure'])->name('payments.failure');

require __DIR__.'/settings.php';
