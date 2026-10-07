<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Support\InspectionSteps;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A draft or submitted inspection on the contractor home (CH-01 / CK-01).
 *
 * @mixin Inspection
 */
class InspectionCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $updated = $this->updated_at;

        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'ticketNo' => $this->ticket_no,
            'ownerName' => $this->owner_name,
            'address' => $this->property_address ? (string) str($this->property_address)->squish() : null,
            'area' => $this->serviceArea?->name,
            'step' => $this->current_step,
            'stepLabel' => InspectionSteps::shortLabel($this->current_step),
            'savedLabel' => match (true) {
                $updated === null => null,
                $updated->isToday() => 'saved '.$updated->format('H:i'),
                $updated->isYesterday() => 'saved yesterday',
                default => 'saved '.$updated->format('j M'),
            },
            'submittedAt' => $this->submitted_at?->format('d M Y'),
            'amount' => null,
        ];
    }
}
