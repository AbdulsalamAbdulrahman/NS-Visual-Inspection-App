<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

/**
 * Mirrors Inspection::scopeVisibleTo so lists and single records agree.
 */
class InspectionPolicy
{
    public function view(User $user, Inspection $inspection): bool
    {
        return match (true) {
            $user->isAdmin() => true,
            $user->isContractor() => $inspection->contractor_id === $user->id,
            $user->isRep() => $inspection->isSubmitted()
                && $inspection->service_area_id !== null
                && $user->serviceAreas()->whereKey($inspection->service_area_id)->exists(),
            default => false,
        };
    }

    /** Only the owning contractor, while it's a draft or sent back for changes. */
    public function update(User $user, Inspection $inspection): bool
    {
        return $this->owns($user, $inspection) && $inspection->isEditable();
    }

    /** Paying submits a draft; a report sent back for changes is resubmitted without paying again. */
    public function pay(User $user, Inspection $inspection): bool
    {
        return $this->owns($user, $inspection) && $inspection->isDraft();
    }

    public function resubmit(User $user, Inspection $inspection): bool
    {
        return $this->owns($user, $inspection) && $inspection->needsChanges();
    }

    /** NSD admins approve or send back reports waiting in the review queue. */
    public function review(User $user, Inspection $inspection): bool
    {
        return $user->isAdmin() && $inspection->isAwaitingReview();
    }

    /** The full report prints once it has a ticket, i.e. once it's submitted. */
    public function print(User $user, Inspection $inspection): bool
    {
        return $inspection->isSubmitted() && $this->view($user, $inspection);
    }

    /** A certificate exists only after NSD approval. */
    public function certificate(User $user, Inspection $inspection): bool
    {
        return $inspection->isApproved() && $this->view($user, $inspection);
    }

    /** The assigned contractor turns a job into a draft when they're at the property. */
    public function start(User $user, Inspection $inspection): bool
    {
        return $this->owns($user, $inspection) && $inspection->isAssigned();
    }

    /** A rep changes, reassigns or cancels a job in their areas until it's started. */
    public function changeJob(User $user, Inspection $inspection): bool
    {
        return $user->isRep()
            && $inspection->isAssigned()
            && $inspection->hasRepDetails()
            && $inspection->service_area_id !== null
            && $user->serviceAreas()->whereKey($inspection->service_area_id)->exists();
    }

    private function owns(User $user, Inspection $inspection): bool
    {
        return $user->isContractor() && $inspection->contractor_id === $user->id;
    }
}
