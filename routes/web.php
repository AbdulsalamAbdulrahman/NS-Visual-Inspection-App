<?php

declare(strict_types=1);

use App\Http\Controllers\Account\ContractorDocumentController;
use App\Http\Controllers\Account\FirstPasswordController;
use App\Http\Controllers\Account\OnboardingController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CertificateSettingsController;
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\ContractorImportController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\InspectionController as AdminInspectionController;
use App\Http\Controllers\Admin\LecanController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\RepController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ServiceAreaController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CustomerDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\Lecan\InspectionController as LecanInspectionController;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Rep\InspectionController as RepInspectionController;
use App\Http\Controllers\Rep\JobController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VerifyController;
use App\Models\ContractorProfile;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Public check behind the certificate QR code (no personal data beyond a masked name).
Route::get('verify/{ticket}', VerifyController::class)
    ->middleware('throttle:30,1')
    ->where('ticket', '[A-Za-z0-9-]{1,40}')
    ->name('verify');

// The building owner's copies from emailed links: signed, no account (see DocumentUrls).
Route::middleware(['signed', 'throttle:60,1'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('inspections/{inspection}/receipt', [CustomerDocumentController::class, 'receipt'])->name('receipt');
    Route::get('inspections/{inspection}/certificate', [CustomerDocumentController::class, 'certificate'])->name('certificate');
    Route::get('inspections/{inspection}/report', [CustomerDocumentController::class, 'report'])->name('report');
    Route::get('inspections/{inspection}/signature', [CustomerDocumentController::class, 'signature'])->name('files.signature');
    Route::get('inspections/{inspection}/signatory', [CustomerDocumentController::class, 'signatory'])->name('files.signatory');
    Route::get('attachments/{attachment}', [CustomerDocumentController::class, 'attachment'])->name('files.attachment');
});

// Monnify payment notifications (CSRF-exempt, see bootstrap/app.php).
Route::post('webhooks/monnify', MonnifyWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.monnify');

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
        Route::get('welcome/documents', [OnboardingController::class, 'show'])->name('onboarding');
        Route::post('profile/documents', [ContractorDocumentController::class, 'store'])->middleware('throttle:30,1')->name('profile.documents.store');
        Route::get('profile/documents/{document}', [ContractorDocumentController::class, 'show'])->whereIn('document', ContractorProfile::DOCUMENTS)->name('profile.documents.show');

        Route::get('inspections', [InspectionController::class, 'index'])->name('inspections.index');
        Route::get('inspections/{inspection}', [InspectionController::class, 'show'])->name('inspections.show');
        Route::post('inspections/{inspection}/start', [InspectionController::class, 'start'])->name('inspections.start');
        Route::get('inspections/{inspection}/edit', [InspectionController::class, 'edit'])->name('inspections.edit');
        Route::put('inspections/{uuid}/draft', [InspectionController::class, 'saveDraft'])->name('inspections.draft');
        Route::post('inspections/{inspection}/resubmit', [InspectionController::class, 'resubmit'])->name('inspections.resubmit');

        Route::get('inspections/{inspection}/pay', [PaymentController::class, 'create'])->name('inspections.pay');
        Route::post('inspections/{inspection}/pay', [PaymentController::class, 'store'])->middleware('throttle:10,1')->name('inspections.pay.store');
        Route::get('inspections/{inspection}/ticket', TicketController::class)->name('inspections.ticket');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->middleware('throttle:40,1')->name('payments.verify');

        Route::post('inspections/{inspection}/attachments', [AttachmentController::class, 'store'])->name('inspections.attachments.store');
        Route::delete('inspections/{inspection}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('inspections.attachments.destroy');
    });

    // Shared by every role; access is checked by policy (role + service area).
    Route::get('inspections/{inspection}/signature', [InspectionController::class, 'signature'])->name('inspections.signature');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('inspections/{inspection}/print', [PrintController::class, 'report'])->name('inspections.print');
    Route::get('inspections/{inspection}/certificate', [PrintController::class, 'certificate'])->name('inspections.certificate');
    Route::get('inspections/{inspection}/certificate/signatory', [PrintController::class, 'signatorySignature'])->name('inspections.certificate.signature');
    Route::get('inspections/{inspection}/receipt', [PrintController::class, 'receipt'])->name('inspections.receipt');
    Route::post('inspections/{inspection}/receipt/send', [ReceiptController::class, 'send'])->middleware('throttle:6,1')->name('inspections.receipt.send');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', OverviewController::class)->name('overview');

        Route::get('inspections', [AdminInspectionController::class, 'index'])->name('inspections.index');
        Route::get('inspections/export', [AdminInspectionController::class, 'export'])->name('inspections.export');
        Route::get('inspections/{inspection}', [AdminInspectionController::class, 'show'])->name('inspections.show');
        Route::post('inspections/{inspection}/approve', [ReviewController::class, 'approve'])->name('inspections.approve');
        Route::post('inspections/{inspection}/request-changes', [ReviewController::class, 'requestChanges'])->name('inspections.request-changes');

        Route::get('certificate', [CertificateSettingsController::class, 'edit'])->name('certificate.edit');
        Route::post('certificate', [CertificateSettingsController::class, 'update'])->name('certificate.update');
        Route::get('certificate/signature', [CertificateSettingsController::class, 'signature'])->name('certificate.signature');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [AdminPaymentController::class, 'export'])->name('payments.export');
        Route::inertia('more', 'admin/More')->name('more');

        Route::get('contractors', [ContractorController::class, 'index'])->name('contractors.index');
        Route::post('contractors', [ContractorController::class, 'store'])->name('contractors.store');
        Route::get('contractors/template.csv', [ContractorImportController::class, 'template'])->name('contractors.template');
        Route::get('contractors/import', [ContractorImportController::class, 'preview'])->name('contractors.import');
        Route::post('contractors/import', [ContractorImportController::class, 'upload'])->middleware('throttle:20,1')->name('contractors.import.upload');
        Route::post('contractors/import/confirm', [ContractorImportController::class, 'confirm'])->name('contractors.import.confirm');
        Route::delete('contractors/import', [ContractorImportController::class, 'discard'])->name('contractors.import.discard');
        Route::put('contractors/{contractor}', [ContractorController::class, 'update'])->name('contractors.update');
        Route::get('contractors/{contractor}/documents/{document}', [ContractorDocumentController::class, 'forAdmin'])->whereIn('document', ContractorProfile::DOCUMENTS)->name('contractors.documents.show');

        Route::get('lecan', [LecanController::class, 'index'])->name('lecan.index');
        Route::post('lecan', [LecanController::class, 'store'])->name('lecan.store');
        Route::put('lecan/{official}', [LecanController::class, 'update'])->name('lecan.update');
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

    Route::middleware('role:lecan')->prefix('lecan')->name('lecan.')->group(function () {
        Route::get('inspections', [LecanInspectionController::class, 'index'])->name('inspections.index');
        Route::get('inspections/{inspection}', [LecanInspectionController::class, 'show'])->name('inspections.show');
        Route::post('inspections/{inspection}/approve', [ReviewController::class, 'approve'])->name('inspections.approve');
        Route::post('inspections/{inspection}/request-changes', [ReviewController::class, 'requestChanges'])->name('inspections.request-changes');
    });

    Route::middleware('role:rep')->prefix('rep')->name('rep.')->group(function () {
        Route::get('inspections', [RepInspectionController::class, 'index'])->name('inspections.index');
        Route::get('inspections/{inspection}', [RepInspectionController::class, 'show'])->name('inspections.show');

        Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/new', [JobController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('jobs/template.csv', [JobController::class, 'template'])->name('jobs.template');
        Route::get('jobs/import', [JobController::class, 'preview'])->name('jobs.import');
        Route::post('jobs/import', [JobController::class, 'upload'])->middleware('throttle:20,1')->name('jobs.import.upload');
        Route::post('jobs/import/confirm', [JobController::class, 'confirm'])->name('jobs.import.confirm');
        Route::delete('jobs/import', [JobController::class, 'discard'])->name('jobs.import.discard');
        Route::get('jobs/{inspection}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{inspection}', [JobController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{inspection}', [JobController::class, 'destroy'])->name('jobs.destroy');
    });
});
