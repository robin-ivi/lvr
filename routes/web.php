<?php

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

require __DIR__.'/settings.php';
