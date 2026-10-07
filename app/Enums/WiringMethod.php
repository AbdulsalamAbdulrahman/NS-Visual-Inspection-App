<?php

declare(strict_types=1);

namespace App\Enums;

enum WiringMethod: string
{
    case Surface = 'surface';
    case Conduit = 'conduit';
    case Trunking = 'trunking';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Surface => 'Surface',
            self::Conduit => 'Conduit',
            self::Trunking => 'Trunking',
            self::Other => 'Other',
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
