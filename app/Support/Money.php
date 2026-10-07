<?php

declare(strict_types=1);

namespace App\Support;

/**
 * All money is stored as integer kobo (₦1 = 100 kobo).
 */
final class Money
{
    /** 1500000 → "₦15,000.00" */
    public static function format(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, 2);
    }

    /** "15,000" / "15000.50" → 1500050; null when not a valid amount. */
    public static function parseNaira(string|int|float|null $naira): ?int
    {
        if ($naira === null || $naira === '') {
            return null;
        }

        $clean = str_replace([',', '₦', ' '], '', (string) $naira);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return null;
        }

        return (int) round(((float) $clean) * 100);
    }
}
