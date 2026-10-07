<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Enums\InspectionStatus;
use App\Models\Inspection;
use App\Models\User;

class StartInspection
{
    /**
     * Create an empty draft. The uuid may come from the phone so a draft
     * started offline keeps its identity when it syncs.
     */
    public function handle(User $contractor, ?string $uuid = null): Inspection
    {
        $inspection = new Inspection([
            'contractor_id' => $contractor->id,
            'status' => InspectionStatus::Draft,
            'current_step' => 1,
            'inspection_date' => today(),
        ]);

        if ($uuid !== null) {
            $inspection->uuid = $uuid;
        }

        $inspection->save();

        return $inspection;
    }
}
