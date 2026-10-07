<?php

declare(strict_types=1);

namespace App\Enums;

enum VoltageLevel: string
{
    case V240 = '240v';
    case V415 = '415v';
    case Kv11 = '11kv';
    case Kv33 = '33kv';

    public function label(): string
    {
        return match ($this) {
            self::V240 => '240 V',
            self::V415 => '415 V',
            self::Kv11 => '11 kV',
            self::Kv33 => '33 kV',
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
