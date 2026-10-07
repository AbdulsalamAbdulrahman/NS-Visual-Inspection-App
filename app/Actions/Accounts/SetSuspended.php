<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\UserStatus;
use App\Models\User;

class SetSuspended
{
    /**
     * Suspend, or reactivate back to Active (or Invited if they never set a password).
     */
    public function handle(User $user, bool $suspended): void
    {
        $user->forceFill([
            'status' => match (true) {
                $suspended => UserStatus::Suspended,
                $user->must_change_password => UserStatus::Invited,
                default => UserStatus::Active,
            },
        ])->save();
    }
}
