<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use App\Support\TemporaryPassword;

class ResendLoginDetails
{
    /**
     * Issue a fresh temporary password and email it. The user must set their
     * own password again on next sign-in. Suspended accounts stay suspended.
     */
    public function handle(User $user): void
    {
        $password = TemporaryPassword::generate();

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'status' => $user->isSuspended() ? UserStatus::Suspended : UserStatus::Invited,
        ])->save();

        $user->notify(new LoginDetails($password, resent: true));
    }
}
