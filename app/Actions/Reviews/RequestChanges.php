<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\User;
use App\Notifications\ChangesRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * NSD sends a report back with a reason. It reopens for the contractor, who
 * fixes it and resubmits without paying again; the ticket number stays.
 */
class RequestChanges
{
    /**
     * @throws ValidationException when the report was already reviewed
     */
    public function handle(Inspection $inspection, User $admin, string $reason): Inspection
    {
        $returned = DB::transaction(function () use ($inspection, $admin, $reason): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->whereKey($inspection->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isAwaitingReview()) {
                throw ValidationException::withMessages([
                    'review' => 'This report was already reviewed. Reload to see its current status.',
                ]);
            }

            $locked->forceFill([
                'review_status' => ReviewStatus::ChangesRequested,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            $locked->reviews()->create(['user_id' => $admin->id, 'action' => ReviewAction::ChangesRequested, 'note' => $reason]);

            return $locked;
        });

        $returned->contractor->notify(new ChangesRequested($returned));

        return $returned;
    }
}
