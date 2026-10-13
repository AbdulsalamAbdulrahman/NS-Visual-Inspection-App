<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Support\Form74Range;
use App\Support\InspectionSteps;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A job a rep created: the customer's details, who it's assigned to and how
 * far the contractor has got. Used by the rep's job list and job form, and
 * the contractor's "Assigned to you" cards.
 *
 * @mixin Inspection
 */
class JobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            /** not_started | in_progress | submitted */
            'progress' => match (true) {
                $this->isAssigned() => 'not_started',
                $this->isDraft() => 'in_progress',
                default => 'submitted',
            },
            'progressLabel' => match (true) {
                $this->isAssigned() => 'Not started',
                $this->isDraft() => 'In progress · '.InspectionSteps::shortLabel($this->current_step),
                default => 'Submitted',
            },
            'ticketNo' => $this->ticket_no,
            'review' => $this->review_status?->value,
            'reviewLabel' => $this->review_status?->label(),
            'ownerName' => $this->owner_name,
            'ownerPhone' => $this->owner_phone,
            'ownerEmail' => $this->owner_email,
            'address' => $this->property_address ? (string) str($this->property_address)->squish() : null,
            'propertyAddress' => $this->property_address,
            'areaId' => $this->service_area_id,
            'area' => $this->serviceArea?->name,
            'purpose' => $this->purpose?->value,
            'buildingType' => $this->building_type?->value,
            'building' => $this->building_type?->label(),
            'form74No' => $this->form74_no,
            'form74LastNo' => $this->form74_last_no,
            'form74' => Form74Range::label($this->form74_no, $this->form74_last_no),
            'structures' => $this->structure_count,
            'contractorUuid' => $this->contractor->uuid,
            'contractor' => $this->contractor->name,
            'assignedBy' => $this->assigner?->name,
            'assignedAt' => $this->assigned_at?->format('d M Y'),
        ];
    }
}
