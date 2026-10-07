<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Enums\AttachmentType;
use App\Models\Inspection;
use App\Models\InspectionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreAttachment
{
    /**
     * Store on the private disk under inspections/{uuid}/ and record it.
     *
     * @param  array{exif_lat?: float|string|null, exif_lng?: float|string|null, taken_at?: string|null}  $meta
     */
    public function handle(Inspection $inspection, AttachmentType $type, UploadedFile $file, array $meta = []): InspectionAttachment
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $name = $type->value.'-'.Str::lower(Str::random(12)).'.'.$extension;
        $path = $file->storeAs($inspection->storageDirectory(), $name, 'local');

        return $inspection->attachments()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'exif_lat' => $meta['exif_lat'] ?? null,
            'exif_lng' => $meta['exif_lng'] ?? null,
            'taken_at' => $meta['taken_at'] ?? null,
        ]);
    }
}
