<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\InspectionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\Payment;
use App\Notifications\InspectionSubmitted;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single place a confirmed payment turns a draft into a submitted
 * inspection. Called by both the verify endpoint and the webhook; safe to
 * call any number of times, concurrently.
 */
class FinalizePaidInspection
{
    /**
     * Two "first ticket of the year" inserts can still deadlock; the whole
     * transaction is rolled back and simply run again.
     */
    private const DEADLOCK_ATTEMPTS = 5;

    public function __construct(private readonly GenerateTicket $tickets) {}

    /**
     * @param  array<string, mixed>  $transaction  Monnify's verified transaction (responseBody)
     */
    public function handle(Payment $payment, array $transaction): Inspection
    {
        return DB::transaction(function () use ($payment, $transaction): Inspection {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked->inspection()->firstOrFail();
            }

            /** @var Inspection $inspection */
            $inspection = Inspection::query()->whereKey($locked->inspection_id)->lockForUpdate()->firstOrFail();

            $locked->fill([
                'status' => PaymentStatus::Paid,
                'transaction_reference' => $transaction['transactionReference'] ?? $locked->transaction_reference,
                'amount_paid_kobo' => Money::parseNaira((string) ($transaction['amountPaid'] ?? '')) ?? $locked->amount_kobo,
                'channel' => $transaction['paymentMethod'] ?? null,
                'paid_at' => $this->paidOn($transaction),
                'gateway_payload' => [...($locked->gateway_payload ?? []), 'verified' => $transaction],
            ])->save();

            if ($inspection->isSubmitted()) {
                // Paid twice (e.g. two tabs). The money is recorded; the first ticket stands.
                Log::warning('Inspection already submitted; extra payment recorded.', [
                    'inspection' => $inspection->uuid,
                    'payment' => $locked->payment_reference,
                ]);

                return $inspection;
            }

            $contractor = $inspection->contractor()->with('contractorProfile')->firstOrFail();
            $licence = $contractor->contractorProfile;

            $inspection->forceFill([
                'status' => InspectionStatus::Submitted,
                'review_status' => ReviewStatus::Pending,
                'ticket_no' => $this->tickets->handle(),
                'submitted_at' => now(),
                'current_step' => Inspection::STEPS,
                // Snapshot so later profile edits don't change this report.
                'inspector_name' => $contractor->name,
                'inspector_nemsa_category' => $licence?->nemsa_category,
                'inspector_nemsa_reg_no' => $licence?->nemsa_reg_no,
                'inspector_coren_no' => $licence?->coren_no,
                'inspector_firm_name' => $licence?->firm_name,
            ])->save();

            $inspection->reviews()->create(['user_id' => $contractor->id, 'action' => ReviewAction::Submitted]);

            // Queued; only sent once this transaction has committed.
            $contractor->notify(new InspectionSubmitted($inspection, $locked));

            return $inspection;
        }, attempts: self::DEADLOCK_ATTEMPTS);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function paidOn(array $transaction): CarbonImmutable
    {
        $raw = $transaction['paidOn'] ?? null;

        try {
            return is_string($raw) && $raw !== '' ? CarbonImmutable::parse($raw)->setTimezone(config('app.timezone')) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
