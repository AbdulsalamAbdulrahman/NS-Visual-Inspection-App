<?php

declare(strict_types=1);

use App\Http\Controllers\Account\FirstPasswordController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    // AU-03 first sign-in: replace the emailed temporary password.
    Route::get('welcome/password', [FirstPasswordController::class, 'edit'])->name('password.first');
    Route::put('welcome/password', [FirstPasswordController::class, 'update'])->name('password.first.update');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('profile/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.change');

    Route::middleware('role:contractor')->group(function () {
        Route::inertia('inspections', 'contractor/Home')->name('inspections.index');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::inertia('/', 'admin/Overview')->name('overview');
        Route::inertia('more', 'admin/More')->name('more');
    });

    Route::middleware('role:rep')->prefix('rep')->name('rep.')->group(function () {
        Route::inertia('inspections', 'rep/Inspections')->name('inspections.index');
    });
});
