<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ContractorProfile;

/**
 * One description of a contractor's licence for every screen (Profile, Home,
 * admin list and drawer, rep job forms): valid, expiring within the reminder
 * window, expired, or documents still to upload.
 */
class LicenceStatus
{
    /**
     * @return array{state: 'valid'|'expiring'|'expired'|'missing', label: string, expiresAt: string|null, daysLeft: int|null, missing: list<string>}
     */
    public static function for(?ContractorProfile $profile): array
    {
        $missing = $profile?->documentsMissing() ?? ['Licence copy', 'Licence expiry date', 'Passport photograph', 'Signature'];
        $expires = $profile?->licence_expires_at;
        $daysLeft = $expires === null ? null : (int) today()->diffInDays($expires, false);

        [$state, $label] = match (true) {
            $profile?->isLicenceExpired() === true => ['expired', 'Licence expired '.$expires?->format('d M Y')],
            $daysLeft !== null && $daysLeft <= ContractorProfile::REMINDER_DAYS => ['expiring', $daysLeft === 0 ? 'Licence expires today' : "Licence expires in {$daysLeft} day".($daysLeft === 1 ? '' : 's')],
            $missing !== [] => ['missing', 'Documents missing'],
            default => ['valid', 'Licence valid until '.$expires?->format('d M Y')],
        };

        return [
            'state' => $state,
            'label' => $label,
            'expiresAt' => $expires?->format('d M Y'),
            'daysLeft' => $daysLeft,
            'missing' => $missing,
        ];
    }
}
