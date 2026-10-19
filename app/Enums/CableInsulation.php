<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Section 6 · Cable insulation type. PVC only for now (LECAN, Oct 2026).
 */
enum CableInsulation: string
{
    case Pvc = 'pvc';

    public function label(): string
    {
        return match ($this) {
            self::Pvc => 'PVC',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
