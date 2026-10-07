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

    /** Only the owning contractor, only while it's a draft. */
    public function update(User $user, Inspection $inspection): bool
    {
        return $user->isContractor()
            && $inspection->contractor_id === $user->id
            && $inspection->isDraft();
    }

    public function pay(User $user, Inspection $inspection): bool
    {
        return $this->update($user, $inspection);
    }

    /** Printing needs a ticket, i.e. a submitted inspection. */
    public function print(User $user, Inspection $inspection): bool
    {
        return $inspection->isSubmitted() && $this->view($user, $inspection);
    }
}
