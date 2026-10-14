<?php

declare(strict_types=1);

namespace App\Actions\Jobs;

use App\Enums\InspectionStatus;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\JobAssigned;
use App\Support\Form74Range;
use App\Support\JobRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SaveJob
{
    /**
     * Create a job, or change one that hasn't been started. The contractor is
     * emailed when the job is new or moves to them; the owner only when the
     * job is new and an email was given.
     *
     * @param  array<string, mixed>  $data  validated JobRules input
     */
    public function handle(User $rep, array $data, ?Inspection $job = null): Inspection
    {
        $area = ServiceArea::query()->whereKey($data['service_area_id'])->firstOrFail();
        $contractor = JobRules::contractor((string) $data['contractor'], $area);
        abort_if($contractor === null, 422);

        $isNew = $job === null;
        $job ??= new Inspection(['status' => InspectionStatus::Assigned, 'current_step' => 1]);
        $previousContractor = $job->contractor_id;

        $job->fill([
            'owner_name' => $data['owner_name'],
            'property_address' => $data['property_address'],
            'owner_phone' => $data['owner_phone'],
            'owner_email' => $data['owner_email'] ?? null,
            'service_area_id' => $area->id,
            'purpose' => $data['purpose'],
            'building_type' => $data['building_type'],
            'form74_no' => $data['form74_no'],
            'form74_last_no' => $data['form74_last_no'] ?? null,
            'contractor_id' => $contractor->id,
        ]);

        $count = Form74Range::count($job->form74_no, $job->form74_last_no);
        $job->structure_count = is_int($count) ? $count : 1;

        if ($isNew || $previousContractor !== $contractor->id) {
            $job->assigned_by = $rep->id;
            $job->assigned_at = now();
        }

        DB::transaction(fn () => $job->save());

        // The contractor and the owner both hear who is inspecting, again on reassignment.
        // Jobs from before the email was required may have none.
        if ($isNew || $previousContractor !== $contractor->id) {
            $contractor->notify(new JobAssigned($job));

            if (filled($job->owner_email)) {
                Notification::route('mail', $job->owner_email)->notify(new JobAssigned($job));
            }
        }

        return $job;
    }
}
