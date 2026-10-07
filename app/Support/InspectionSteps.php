<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The nine steps of the form (progress "5 / 9"). Mirrors resources/js/lib/inspection/steps.ts.
 */
final class InspectionSteps
{
    public const LABELS = [
        1 => 'A · Customer information',
        2 => 'B1 · Earthing system',
        3 => 'B2 · Over-current protection',
        4 => 'B3 · Mains checklist',
        5 => 'B4 · Description of wiring',
        6 => 'C · System details',
        7 => 'Attachments',
        8 => 'D · Attestation',
        9 => 'Review',
    ];

    /** Home card line: "B4 · Wiring". */
    public const SHORT = [
        1 => 'A · Customer',
        2 => 'B1 · Earthing',
        3 => 'B2 · Protection',
        4 => 'B3 · Mains',
        5 => 'B4 · Wiring',
        6 => 'C · System',
        7 => 'Attachments',
        8 => 'D · Attestation',
        9 => 'Review',
    ];

    public static function shortLabel(int $step): string
    {
        return self::SHORT[$step] ?? self::SHORT[1];
    }
}
