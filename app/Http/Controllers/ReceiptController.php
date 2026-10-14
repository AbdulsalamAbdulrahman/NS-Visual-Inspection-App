<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Notifications\PaymentReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

/**
 * Resend the payment receipt to the building owner (it also goes automatically
 * when the fee is paid). Anyone who can print the report can send it.
 */
class ReceiptController extends Controller
{
    public function send(Inspection $inspection): RedirectResponse
    {
        Gate::authorize('print', $inspection);
        abort_unless($inspection->paidPayment()->exists(), 404);

        if (blank($inspection->owner_email)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'There’s no email for the owner on this job.']);

            return back();
        }

        Notification::route('mail', $inspection->owner_email)->notify(new PaymentReceipt($inspection));
        Inertia::flash('toast', ['type' => 'success', 'message' => "Receipt sent to {$inspection->owner_email}."]);

        return back();
    }
}
