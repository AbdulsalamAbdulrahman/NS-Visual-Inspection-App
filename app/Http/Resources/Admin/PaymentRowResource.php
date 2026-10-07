<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row on the admin Payments page (AD-09).
 *
 * @mixin Payment
 */
class PaymentRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->transaction_reference ?? $this->payment_reference,
            'ticketNo' => $this->inspection?->ticket_no,
            'inspectionUuid' => $this->inspection?->ticket_no ? $this->inspection->uuid : null,
            'contractor' => $this->contractor?->name,
            'amount' => Money::format($this->amount_paid_kobo ?? $this->amount_kobo),
            'channel' => $this->channelLabel(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'date' => ($this->paid_at ?? $this->created_at)?->format('d M, H:i'),
        ];
    }
}
