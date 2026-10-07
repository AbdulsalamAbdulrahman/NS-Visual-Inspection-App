<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Models\InspectionCircuit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * Full editable state of a draft for the 9-step form. Keys match the
 * SaveDraft payload (snake_case) so the client can PATCH it straight back.
 *
 * @mixin Inspection
 */
class DraftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = Arr::only($this->resource->attributesToArray(), Inspection::DRAFT_FIELDS);

        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            ...$fields,
            'gps_captured_at' => $this->gps_captured_at?->toIso8601String(),
            'declaration_accepted' => $this->declaration_accepted_at !== null,
            'inspection_date' => $this->inspection_date?->format('d M Y'),
            'signature_url' => $this->signature_path ? route('inspections.signature', $this->resource).'?v='.md5($this->signature_path) : null,
            'circuits' => $this->circuits->map(fn (InspectionCircuit $c): array => [
                'uuid' => $c->uuid,
                'description' => $c->description?->value,
                'description_other' => $c->description_other,
                'rating_a' => $c->rating_a,
                'conductor_mm2' => $c->conductor_mm2,
                'condition' => $c->condition?->value,
                'observation' => $c->observation,
            ])->values(),
            'attachments' => AttachmentResource::collection($this->attachments),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
