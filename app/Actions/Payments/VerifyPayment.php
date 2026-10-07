<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Monnify\MonnifyClient;
use App\Support\Money;

class VerifyPayment
{
    public function __construct(
        private readonly MonnifyClient $monnify,
        private readonly FinalizePaidInspection $finalize,
    ) {}

    /**
     * Ask Monnify (never the browser) what happened, then act on it.
     * Paid only when PAID/OVERPAID and the amount covers what we asked for.
     */
    public function handle(Payment $payment): PaymentStatus
    {
        if ($payment->isPaid()) {
            return PaymentStatus::Paid;
        }

        $transaction = $this->monnify->findByPaymentReference($payment->payment_reference);

        if ($transaction === null) {
            return $payment->status;
        }

        $status = strtoupper((string) ($transaction['paymentStatus'] ?? ''));
        $paidKobo = Money::parseNaira((string) ($transaction['amountPaid'] ?? '')) ?? 0;

        if (in_array($status, ['PAID', 'OVERPAID'], true) && $paidKobo >= $payment->amount_kobo) {
            $this->finalize->handle($payment, $transaction);

            return PaymentStatus::Paid;
        }

        if (in_array($status, ['FAILED', 'EXPIRED', 'REVERSED', 'PARTIALLY_PAID', 'PAID', 'OVERPAID'], true) && $payment->status === PaymentStatus::Pending) {
            // PAID with too little money lands here too: treat as failed, keep the draft.
            $payment->update([
                'status' => PaymentStatus::Failed,
                'transaction_reference' => $transaction['transactionReference'] ?? $payment->transaction_reference,
                'amount_paid_kobo' => $paidKobo ?: null,
                'channel' => $transaction['paymentMethod'] ?? $payment->channel,
                'gateway_payload' => [...($payment->gateway_payload ?? []), 'verified' => $transaction],
            ]);

            return PaymentStatus::Failed;
        }

        // PENDING (or anything unknown): still waiting.
        return $payment->status;
    }
}
