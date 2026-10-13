<?php

declare(strict_types=1);

namespace App\Support;

/**
 * An estate's Form 74 numbers: the first and last share a prefix and end in
 * a number, e.g. F74/KD/2026/08841 – F74/KD/2026/08860 = 20 structures. The
 * last can also be typed as just its number (08860).
 */
final class Form74Range
{
    public const MAX_STRUCTURES = 500;

    /**
     * @return int|string the number of structures, or a message saying what's wrong
     */
    public static function count(?string $first, ?string $last): int|string
    {
        $first = trim((string) $first);
        $last = trim((string) $last);

        if ($last === '') {
            return 1;
        }

        if (! preg_match('#^(.*?)(\d+)$#', $first, $a)) {
            return 'The first Form 74 number must end in digits, e.g. F74/KD/2026/08841.';
        }

        $lastNumber = str_contains($last, '/') ? (preg_match('#^(.*?)(\d+)$#', $last, $b) ? $b : null) : (ctype_digit($last) ? [$last, $a[1], $last] : null);

        if ($lastNumber === null || strcasecmp($lastNumber[1], $a[1]) !== 0) {
            return 'The last Form 74 number must have the same prefix as the first ('.$a[1].'…).';
        }

        $count = (int) $lastNumber[2] - (int) $a[2] + 1;

        return match (true) {
            $count < 2 => 'The last Form 74 number must be after the first.',
            $count > self::MAX_STRUCTURES => 'That range covers more than '.self::MAX_STRUCTURES.' structures. Check the numbers.',
            default => $count,
        };
    }

    /** "F74/KD/2026/08841 – 08860" for display. */
    public static function label(?string $first, ?string $last): ?string
    {
        if (blank($first)) {
            return null;
        }

        if (blank($last)) {
            return $first;
        }

        $tail = preg_match('#(\d+)$#', (string) $last, $m) ? $m[1] : $last;

        return "{$first} – {$tail}";
    }
}
