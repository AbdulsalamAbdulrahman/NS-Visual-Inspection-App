<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Enums\InspectionStatus;
use App\Models\Inspection;

class StartInspection
{
    /**
     * The contractor is at the property: the assigned job becomes a draft
     * dated today, with the rep's customer details already filled in.
     */
    public function handle(Inspection $job): Inspection
    {
        $job->update([
            'status' => InspectionStatus::Draft,
            'current_step' => 1,
            'inspection_date' => today(),
        ]);

        return $job;
    }
}
