<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Support\Money;
use App\Support\TicketQr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CT-01 / CK-04: the success screen with ticket and QR code.
 */
class TicketController extends Controller
{
    public function __invoke(Inspection $inspection): Response
    {
        Gate::authorize('view', $inspection);
        abort_unless($inspection->isSubmitted(), 404);

        $inspection->loadMissing(['serviceArea', 'paidPayment']);
        $payment = $inspection->paidPayment;

        return Inertia::render('contractor/Ticket', [
            'ticket' => [
                'uuid' => $inspection->uuid,
                'ticketNo' => $inspection->ticket_no,
                'qr' => TicketQr::dataUri((string) $inspection->ticket_no),
                'ownerName' => $inspection->owner_name,
                'address' => $inspection->property_address ? (string) str($inspection->property_address)->squish() : null,
                'area' => $inspection->serviceArea?->name,
                'amountPaid' => $payment ? Money::format($payment->amount_paid_kobo ?? $payment->amount_kobo) : null,
                'paymentReference' => $payment->transaction_reference ?? $payment?->payment_reference,
                'confirmedAt' => ($payment->paid_at ?? $inspection->submitted_at)?->format('H:i'),
                'date' => $inspection->submitted_at?->format('d M Y, H:i'),
                'reportUrl' => Route::has('inspections.show') ? route('inspections.show', $inspection) : null,
                'printUrl' => Route::has('inspections.print') ? route('inspections.print', $inspection) : null,
            ],
        ]);
    }
}
