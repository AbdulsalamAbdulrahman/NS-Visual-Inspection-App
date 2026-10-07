<?php

declare(strict_types=1);

use App\Http\Controllers\Account\FirstPasswordController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\RepController;
use App\Http\Controllers\Admin\ServiceAreaController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectionController;
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
        Route::get('inspections', [InspectionController::class, 'index'])->name('inspections.index');
        Route::post('inspections', [InspectionController::class, 'store'])->name('inspections.store');
        Route::get('inspections/{inspection}/edit', [InspectionController::class, 'edit'])->name('inspections.edit');
        Route::put('inspections/{uuid}/draft', [InspectionController::class, 'saveDraft'])->name('inspections.draft');

        Route::post('inspections/{inspection}/attachments', [AttachmentController::class, 'store'])->name('inspections.attachments.store');
        Route::delete('inspections/{inspection}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('inspections.attachments.destroy');
    });

    // Shared by every role; access is checked by policy (role + service area).
    Route::get('inspections/{inspection}/signature', [InspectionController::class, 'signature'])->name('inspections.signature');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::inertia('/', 'admin/Overview')->name('overview');
        Route::inertia('more', 'admin/More')->name('more');

        Route::get('contractors', [ContractorController::class, 'index'])->name('contractors.index');
        Route::post('contractors', [ContractorController::class, 'store'])->name('contractors.store');
        Route::put('contractors/{contractor}', [ContractorController::class, 'update'])->name('contractors.update');

        Route::get('reps', [RepController::class, 'index'])->name('reps.index');
        Route::post('reps', [RepController::class, 'store'])->name('reps.store');
        Route::put('reps/{rep}', [RepController::class, 'update'])->name('reps.update');

        // Row actions shared by contractors and reps.
        Route::controller(AccountController::class)->prefix('accounts/{user}')->name('accounts.')->group(function () {
            Route::post('resend-login', 'resendLogin')->name('resend-login');
            Route::post('reset-link', 'sendResetLink')->name('reset-link');
            Route::post('suspend', 'suspend')->name('suspend');
            Route::post('reactivate', 'reactivate')->name('reactivate');
            Route::delete('/', 'destroy')->name('destroy');
        });

        Route::get('areas', [ServiceAreaController::class, 'index'])->name('areas.index');
        Route::post('areas', [ServiceAreaController::class, 'store'])->name('areas.store');
        Route::put('areas/{area}', [ServiceAreaController::class, 'update'])->name('areas.update');
        Route::post('areas/{area}/toggle', [ServiceAreaController::class, 'toggle'])->name('areas.toggle');

        Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('fees', [FeeController::class, 'store'])->name('fees.store');
        Route::delete('fees/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');
    });

    Route::middleware('role:rep')->prefix('rep')->name('rep.')->group(function () {
        Route::inertia('inspections', 'rep/Inspections')->name('inspections.index');
    });
});
