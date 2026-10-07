<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class TemporaryPassword
{
    /**
     * A one-time password that satisfies the password rules and is easy to
     * read out or type from an email (no look-alike characters).
     */
    public static function generate(): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyz';
        $pick = fn (string $pool, int $n): string => implode('', array_map(
            fn (): string => $pool[random_int(0, strlen($pool) - 1)],
            range(1, $n),
        ));

        return Str::ucfirst($pick($letters, 4)).'-'.$pick('23456789', 4).'-'.$pick($letters, 4);
    }
}
