<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * A contractor whose licence has expired tried to start, pay for or resubmit
 * an inspection. They go back to My inspections with the reason; the
 * renewed licence is uploaded on Profile.
 */
class LicenceExpiredException extends Exception
{
    public static function check(User $user): void
    {
        $profile = $user->contractorProfile;

        if ($user->isContractor() && $profile?->isLicenceExpired() === true) {
            throw new self('Your licence expired on '.$profile->licence_expires_at?->format('d M Y').'. Upload the renewed licence and its new expiry date on Profile to continue.');
        }
    }

    public function render(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $this->getMessage()]);

        return to_route('inspections.index');
    }
}
