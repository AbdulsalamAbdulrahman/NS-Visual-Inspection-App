<?php

declare(strict_types=1);

namespace App\Enums;

enum ConductorType: string
{
    case Copper = 'copper';
    case Aluminium = 'aluminium';

    public function label(): string
    {
        return match ($this) {
            self::Copper => 'Copper',
            self::Aluminium => 'Aluminium',
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
