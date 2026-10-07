<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InspectionAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InspectionAttachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size_bytes,
            'isImage' => $this->isImage(),
            'url' => route('attachments.show', $this->resource),
            'exifLat' => $this->exif_lat,
            'exifLng' => $this->exif_lng,
            'takenAt' => $this->taken_at?->toIso8601String(),
        ];
    }
}
