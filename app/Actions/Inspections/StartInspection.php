<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Enums\InspectionStatus;
use App\Models\Inspection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StartInspection
{
    /**
     * The contractor is at the property: the assigned job becomes a draft
     * dated today, with the rep's customer details already filled in and the
     * contractor's signature on file copied in (they can redraw it). It's a
     * copy, so a later profile change never alters a report.
     */
    public function handle(Inspection $job): Inspection
    {
        $job->update([
            'status' => InspectionStatus::Draft,
            'current_step' => 1,
            'inspection_date' => today(),
            'signature_path' => $job->signature_path ?? $this->copyProfileSignature($job),
        ]);

        return $job;
    }

    private function copyProfileSignature(Inspection $job): ?string
    {
        $source = $job->contractor->contractorProfile?->signature_path;
        $disk = Storage::disk('local');

        if ($source === null || ! $disk->exists($source)) {
            return null;
        }

        $path = $job->storageDirectory().'/signature-'.Str::random(8).'.'.pathinfo($source, PATHINFO_EXTENSION);
        $disk->copy($source, $path);

        return $path;
    }
}
