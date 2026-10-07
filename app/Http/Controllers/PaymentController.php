<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\InitPayment;
use App\Actions\Payments\VerifyPayment;
use App\Enums\PaymentStatus;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\Payment;
use App\Services\Monnify\MonnifyException;
use App\Support\InspectionChecklist;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Pay → Monnify checkout (full-page redirect) → return page that polls our
 * verify endpoint until Monnify confirms (CP-01, CP-02, CP-03).
 */
class PaymentController extends Controller
{
    /** CP-01: fee and summary before paying (phones; desktop pays from Review). */
    public function create(Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('pay', $inspection);

        if (! InspectionChecklist::isComplete($inspection)) {
            return to_route('inspections.edit', ['inspection' => $inspection, 'step' => Inspection::STEPS]);
        }

        $fee = FeeSchedule::current();
        $inspection->loadMissing('serviceArea');

        return Inertia::render('contractor/Pay', [
            'inspection' => [
                'uuid' => $inspection->uuid,
                'ownerName' => $inspection->owner_name,
                'area' => $inspection->serviceArea?->name,
                'form74No' => $inspection->form74_no,
            ],
            'fee' => $fee ? [
                'amount' => Money::format($fee->amount_kobo),
                'effectiveFrom' => $fee->effective_from->format('d M Y'),
            ] : null,
        ]);
    }

    public function store(Inspection $inspection, InitPayment $init): HttpResponse
    {
        Gate::authorize('pay', $inspection);

        try {
            ['checkoutUrl' => $url] = $init->handle($inspection);
        } catch (MonnifyException $e) {
            Log::error('Monnify checkout failed to start', ['inspection' => $inspection->uuid, 'error' => $e->getMessage()]);
            Inertia::flash('toast', ['type' => 'error', 'message' => "Couldn't open Monnify checkout. Check your connection and try again. Your draft is saved."]);

            return back();
        }

        // External redirect to Monnify's hosted checkout.
        return Inertia::location($url);
    }

    /** Return page (Monnify's redirectUrl) — CP-02 processing / CP-03 failed. */
    public function show(Request $request, Payment $payment): Response|RedirectResponse
    {
        abort_unless($request->user()->is($payment->contractor), 403);

        if ($payment->isPaid()) {
            return to_route('inspections.ticket', $payment->inspection);
        }

        return Inertia::render('contractor/PaymentStatus', [
            'payment' => [
                'reference' => $payment->payment_reference,
                'transactionReference' => $payment->transaction_reference,
                'status' => $payment->status->value,
                'amount' => Money::format($payment->amount_kobo),
                'updatedAt' => $payment->updated_at?->format('H:i'),
                'reason' => $this->failureReason($payment),
            ],
            'inspection' => ['uuid' => $payment->inspection->uuid],
        ]);
    }

    /** Polled by the return page; asks Monnify server-side. */
    public function verify(Request $request, Payment $payment, VerifyPayment $verify): JsonResponse
    {
        abort_unless($request->user()->is($payment->contractor), 403);

        try {
            $status = $verify->handle($payment);
        } catch (MonnifyException $e) {
            Log::warning('Monnify verify failed', ['payment' => $payment->payment_reference, 'error' => $e->getMessage()]);
            $status = $payment->fresh()->status;
        }

        $payment->refresh();

        return response()->json([
            'status' => $status->value,
            'transactionReference' => $payment->transaction_reference,
            'reason' => $this->failureReason($payment),
            'ticketUrl' => $status === PaymentStatus::Paid ? route('inspections.ticket', $payment->inspection) : null,
        ]);
    }

    private function failureReason(Payment $payment): ?string
    {
        if ($payment->status !== PaymentStatus::Failed) {
            return null;
        }

        return match (strtoupper((string) data_get($payment->gateway_payload, 'verified.paymentStatus'))) {
            'EXPIRED' => 'The payment session expired before any money was received. You have not been charged.',
            'REVERSED' => 'The payment was reversed by the bank. Any money taken is returned to you.',
            'PARTIALLY_PAID' => 'Only part of the fee was received. Contact the New Service Department about the part payment.',
            'PAID', 'OVERPAID' => 'The amount received was less than the fee. Contact the New Service Department.',
            default => data_get($payment->gateway_payload, 'init_error')
                ? "Monnify couldn't start the payment. You have not been charged."
                : 'Monnify reported the payment failed. You have not been charged.',
        };
    }
}
