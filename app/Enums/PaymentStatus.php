<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    /** Pending for more than 24 hours (marked by a scheduled command). */
    case Abandoned = 'abandoned';

    /** Admin Payments page wording (AD-09). */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Successful',
            self::Failed => 'Failed',
            self::Abandoned => 'Abandoned',
        };
    }
}
