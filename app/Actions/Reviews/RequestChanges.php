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
 * LECAN or NSD sends a report back with a reason, from their own queue. It reopens for the contractor, who
 * fixes it and resubmits without paying again; the ticket number stays.
 */
class RequestChanges
{
    /**
     * @throws ValidationException when the report was already reviewed
     */
    public function handle(Inspection $inspection, User $reviewer, string $reason): Inspection
    {
        $returned = DB::transaction(function () use ($inspection, $reviewer, $reason): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->whereKey($inspection->getKey())->lockForUpdate()->firstOrFail();

            if (! ($reviewer->isLecan() ? $locked->isAwaitingLecan() : $locked->isAwaitingNsd())) {
                throw ValidationException::withMessages([
                    'review' => 'This report was already reviewed. Reload to see its current status.',
                ]);
            }

            $locked->forceFill([
                'review_status' => ReviewStatus::ChangesRequested,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            $locked->reviews()->create(['user_id' => $reviewer->id, 'action' => ReviewAction::ChangesRequested, 'note' => $reason]);

            return $locked;
        });

        $returned->contractor->notify(new ChangesRequested($returned, $reviewer->isLecan() ? 'LECAN' : 'NSD'));

        return $returned;
    }
}
