<?php

declare(strict_types=1);

namespace App\Enums;

enum FuseType: string
{
    case Porcelain = 'porcelain';
    case Polymer = 'polymer';

    public function label(): string
    {
        return match ($this) {
            self::Porcelain => 'Porcelain',
            self::Polymer => 'Polymer',
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
