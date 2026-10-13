<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The LECAN official approves a report for their state chapter; it moves to
 * NSD for final approval. Their name is kept for the certificate.
 */
class LecanApprove
{
    /**
     * @throws ValidationException when the report isn't waiting for LECAN
     */
    public function handle(Inspection $inspection, User $official): Inspection
    {
        return DB::transaction(function () use ($inspection, $official): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->whereKey($inspection->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isAwaitingLecan()) {
                throw ValidationException::withMessages([
                    'review' => 'This report was already reviewed. Reload to see its current status.',
                ]);
            }

            $locked->forceFill([
                'review_status' => ReviewStatus::PendingNsd,
                'review_note' => null,
                'lecan_approved_by' => $official->id,
                'lecan_approved_at' => now(),
                'lecan_approver_name' => $official->name,
            ])->save();

            $locked->reviews()->create(['user_id' => $official->id, 'action' => ReviewAction::LecanApproved]);

            return $locked;
        });
    }
}
