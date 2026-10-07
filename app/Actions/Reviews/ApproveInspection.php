<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InspectionApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * NSD approves a report: the certificate is issued. The signatory (name,
 * title, signature image) is copied onto the inspection so later changes to
 * the settings never alter a certificate that was already issued.
 */
class ApproveInspection
{
    /**
     * @throws ValidationException when no signatory is set or the report was already reviewed
     */
    public function handle(Inspection $inspection, User $admin): Inspection
    {
        $signatory = Setting::signatory();

        if ($signatory === null) {
            throw ValidationException::withMessages([
                'review' => 'Set the certificate signatory (More → Certificate) before approving.',
            ]);
        }

        $approved = DB::transaction(function () use ($inspection, $admin, $signatory): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->whereKey($inspection->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isAwaitingReview()) {
                throw ValidationException::withMessages([
                    'review' => 'This report was already reviewed. Reload to see its current status.',
                ]);
            }

            $locked->forceFill([
                'review_status' => ReviewStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'review_note' => null,
                'signatory_name' => $signatory['name'],
                'signatory_title' => $signatory['title'],
                'signatory_signature_path' => $this->copySignature($locked, $signatory['signature_path']),
            ])->save();

            $locked->reviews()->create(['user_id' => $admin->id, 'action' => ReviewAction::Approved]);

            return $locked;
        });

        $approved->contractor->notify(new InspectionApproved($approved));

        return $approved;
    }

    private function copySignature(Inspection $inspection, string $source): string
    {
        $disk = Storage::disk('local');
        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'png';
        $target = $inspection->storageDirectory()."/nsd-signature.{$extension}";

        if (! $disk->exists($source)) {
            throw ValidationException::withMessages([
                'review' => 'The signatory\'s signature image is missing. Upload it again under More → Certificate.',
            ]);
        }

        $disk->delete($target);
        $disk->copy($source, $target);

        return $target;
    }
}
