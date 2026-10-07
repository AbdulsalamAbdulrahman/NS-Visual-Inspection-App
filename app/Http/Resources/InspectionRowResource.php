<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A submitted inspection in a list (AD-02, AM-02, SR-D1/T1/M1). The amount is
 * only included for admins — reps never see payment data.
 *
 * @mixin Inspection
 */
class InspectionRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->relationLoaded('paidPayment') ? $this->paidPayment : null;

        return [
            'uuid' => $this->uuid,
            'ticketNo' => $this->ticket_no,
            'ownerName' => $this->owner_name,
            'address' => $this->property_address ? (string) str($this->property_address)->squish() : null,
            'area' => $this->serviceArea?->name,
            'contractor' => $this->inspector_name ?? $this->contractor?->name,
            'submittedAt' => $this->submitted_at?->format('d M Y'),
            'purpose' => $this->purpose?->label(),
            'connection' => $this->connection_type?->label(),
            'amount' => $this->when(
                (bool) $request->user()?->isAdmin(),
                fn () => $payment ? Money::format($payment->amount_paid_kobo ?? $payment->amount_kobo) : null,
            ),
        ];
    }
}
