<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\UserStatus;
use App\Models\ContractorProfile;
use App\Notifications\LicenceExpired;
use App\Notifications\LicenceExpiringSoon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daily: email contractors whose licence expires within a month, and those
 * whose licence has just expired (they can't take new work until they upload
 * the renewed one). Flags make each email go once per expiry date, even if
 * the scheduler missed a day.
 */
class SendLicenceReminders
{
    /**
     * @return array{reminded: int, expired: int}
     */
    public function handle(): array
    {
        $counts = ['reminded' => 0, 'expired' => 0];
        $contractors = fn (Builder $q) => $q->whereIn('status', [UserStatus::Active, UserStatus::Invited]);

        ContractorProfile::query()
            ->whereHas('user', $contractors)
            ->whereNull('licence_expired_notified_at')
            ->whereDate('licence_expires_at', '<', today())
            ->with('user')
            ->each(function (ContractorProfile $profile) use (&$counts): void {
                $profile->user->notify(new LicenceExpired($profile));
                // An expired licence needs no "expires soon" email any more.
                $profile->forceFill(['licence_expired_notified_at' => now(), 'licence_reminded_at' => $profile->licence_reminded_at ?? now()])->save();
                $counts['expired']++;
            });

        ContractorProfile::query()
            ->whereHas('user', $contractors)
            ->whereNull('licence_reminded_at')
            ->whereDate('licence_expires_at', '>=', today())
            ->whereDate('licence_expires_at', '<=', today()->addDays(ContractorProfile::REMINDER_DAYS))
            ->with('user')
            ->each(function (ContractorProfile $profile) use (&$counts): void {
                $profile->user->notify(new LicenceExpiringSoon($profile));
                $profile->forceFill(['licence_reminded_at' => now()])->save();
                $counts['reminded']++;
            });

        return $counts;
    }
}
