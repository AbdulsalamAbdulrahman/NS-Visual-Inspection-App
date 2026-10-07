<?php

declare(strict_types=1);

namespace App\Enums;

enum EarthingSystemType: string
{
    case Tt = 'tt';
    case TnS = 'tn_s';
    case TnCS = 'tn_c_s';
    case It = 'it';

    public function label(): string
    {
        return match ($this) {
            self::Tt => 'TT',
            self::TnS => 'TN-S',
            self::TnCS => 'TN-C-S',
            self::It => 'IT',
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
