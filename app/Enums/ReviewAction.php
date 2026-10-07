<?php

declare(strict_types=1);

namespace App\Enums;

/** One entry in an inspection's review history. */
enum ReviewAction: string
{
    case Submitted = 'submitted';
    case ChangesRequested = 'changes_requested';
    case Resubmitted = 'resubmitted';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted and paid',
            self::ChangesRequested => 'Changes requested',
            self::Resubmitted => 'Resubmitted',
            self::Approved => 'Approved, certificate issued',
        };
    }
}
