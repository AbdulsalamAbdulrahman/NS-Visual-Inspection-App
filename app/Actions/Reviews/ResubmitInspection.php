<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\User;
use App\Support\InspectionChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The contractor sends a corrected report back to the review queue. The fee
 * was already paid, so there's no payment step; the report must be complete.
 */
class ResubmitInspection
{
    /**
     * @throws ValidationException when the report is incomplete or isn't awaiting changes
     */
    public function handle(Inspection $inspection, User $contractor): Inspection
    {
        $missing = InspectionChecklist::missing($inspection);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'inspection' => 'Fix '.count($missing).' item'.(count($missing) === 1 ? '' : 's').' before resubmitting: '
                    .collect($missing)->pluck('label')->take(5)->join(', ').(count($missing) > 5 ? '…' : '.'),
            ]);
        }

        return DB::transaction(function () use ($inspection, $contractor): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->whereKey($inspection->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->needsChanges()) {
                throw ValidationException::withMessages([
                    'inspection' => 'This report isn\'t waiting for changes.',
                ]);
            }

            $locked->forceFill([
                'review_status' => ReviewStatus::Pending,
                'review_note' => null,
            ])->save();

            $locked->reviews()->create(['user_id' => $contractor->id, 'action' => ReviewAction::Resubmitted]);

            return $locked;
        });
    }
}
