<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Monnify\MonnifyClient;
use App\Services\Monnify\MonnifyException;
use Illuminate\Support\Facades\Log;

/**
 * Payments still pending after a while become Abandoned (AD-09). Each one is
 * checked with Monnify first: a payment that went through but whose
 * notification never arrived is finalised (ticket issued), never abandoned.
 * If Monnify can't be reached, the payment is left for the next run.
 */
class AbandonStalePayments
{
    public function __construct(
        private readonly VerifyPayment $verify,
        private readonly MonnifyClient $monnify,
    ) {}

    /**
     * @return array{abandoned: int, paid: int, failed: int, skipped: int}
     */
    public function handle(int $hours = 24): array
    {
        $counts = ['abandoned' => 0, 'paid' => 0, 'failed' => 0, 'skipped' => 0];
        $canVerify = $this->monnify->isConfigured();

        Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<', now()->subHours($hours))
            ->with('inspection')
            ->lazyById(100)
            ->each(function (Payment $payment) use (&$counts, $canVerify): void {
                if ($canVerify) {
                    try {
                        $status = $this->verify->handle($payment);
                    } catch (MonnifyException $e) {
                        Log::warning('Stale payment not checked; Monnify unavailable.', ['payment' => $payment->payment_reference, 'error' => $e->getMessage()]);
                        $counts['skipped']++;

                        return;
                    }

                    if ($status === PaymentStatus::Paid) {
                        $counts['paid']++;

                        return;
                    }

                    if ($status === PaymentStatus::Failed) {
                        $counts['failed']++;

                        return;
                    }
                }

                // Still pending (or Monnify never saw it): the contractor walked away.
                $payment->update(['status' => PaymentStatus::Abandoned]);
                $counts['abandoned']++;
            });

        return $counts;
    }
}
