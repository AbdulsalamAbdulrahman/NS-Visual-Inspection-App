<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\Payment;
use App\Services\Monnify\MonnifyClient;
use App\Services\Monnify\MonnifyException;
use App\Support\InspectionChecklist;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InitPayment
{
    public function __construct(private readonly MonnifyClient $monnify) {}

    /**
     * Re-check the whole inspection, take the fee in effect right now, record
     * a pending payment with our own reference, and open a Monnify checkout.
     *
     * @return array{payment: Payment, checkoutUrl: string}
     *
     * @throws ValidationException when the inspection is incomplete or no fee is set
     * @throws MonnifyException when Monnify can't start the checkout
     */
    public function handle(Inspection $inspection): array
    {
        $missing = InspectionChecklist::missing($inspection);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'inspection' => 'Fix '.count($missing).' item'.(count($missing) === 1 ? '' : 's').' before paying: '
                    .collect($missing)->pluck('label')->take(5)->join(', ').(count($missing) > 5 ? '…' : '.'),
            ]);
        }

        $fee = FeeSchedule::current();

        if ($fee === null) {
            throw ValidationException::withMessages(['inspection' => 'The inspection fee has not been set yet. Contact the New Service Department.']);
        }

        $contractor = $inspection->contractor;

        $payment = Payment::query()->create([
            'inspection_id' => $inspection->id,
            'contractor_id' => $contractor->id,
            'payment_reference' => 'KENS-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
            'amount_kobo' => $fee->amount_kobo,
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $checkout = $this->monnify->initTransaction(
                amountKobo: $payment->amount_kobo,
                paymentReference: $payment->payment_reference,
                customerName: $contractor->name,
                customerEmail: $contractor->email,
                description: Str::limit("Building electrical inspection fee · {$inspection->owner_name} · {$inspection->form74_no}", 120),
                redirectUrl: route('payments.show', $payment),
            );
        } catch (MonnifyException $e) {
            $payment->update(['status' => PaymentStatus::Failed, 'gateway_payload' => ['init_error' => $e->getMessage()]]);

            throw $e;
        }

        $payment->update([
            'transaction_reference' => $checkout['transactionReference'],
            'gateway_payload' => ['init' => $checkout['raw']],
        ]);

        return ['payment' => $payment, 'checkoutUrl' => $checkout['checkoutUrl']];
    }
}
