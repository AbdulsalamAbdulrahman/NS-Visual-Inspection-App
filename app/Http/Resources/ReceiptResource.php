<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Support\Form74Range;
use App\Support\Money;
use App\Support\TicketQr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The payment receipt: what was paid for which building. Amounts come from the
 * payment as charged (the fee in effect then), never from today's fees.
 *
 * @mixin Inspection
 */
class ReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->paidPayment;
        $paidKobo = $payment === null ? 0 : (int) ($payment->amount_paid_kobo ?? $payment->amount_kobo);
        $structures = max(1, (int) $this->structure_count);
        $ticket = (string) $this->ticket_no;

        return [
            'uuid' => $this->uuid,
            'receiptNo' => $payment->transaction_reference ?? $payment?->payment_reference,
            'paymentReference' => $payment?->payment_reference,
            'ticketNo' => $ticket,
            'paidAt' => ($payment->paid_at ?? $this->submitted_at)?->format('d M Y, H:i'),
            'channel' => $payment?->channel ? str($payment->channel)->replace('_', ' ')->title()->toString() : null,
            'contractor' => [
                'name' => $this->inspector_name ?? $this->contractor->name,
                'firm' => $this->inspector_firm_name ?? $this->contractor->contractorProfile?->firm_name,
            ],
            'ownerName' => $this->owner_name,
            'address' => $this->property_address ? (string) str($this->property_address)->squish() : null,
            'area' => $this->serviceArea?->name,
            'form74No' => Form74Range::label($this->form74_no, $this->form74_last_no),
            'building' => $this->building_type?->label() ?? $this->purpose?->label(),
            'structures' => $structures,
            'unit' => $structures > 1 ? Money::format(intdiv($paidKobo, $structures)) : null,
            'total' => Money::format($paidKobo),
            'verifyUrl' => TicketQr::verifyUrl($ticket),
            'qr' => TicketQr::dataUri($ticket),
        ];
    }
}
