<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyPurpose: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Industrial = 'industrial';
    case Government = 'government';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::Industrial => 'Industrial',
            self::Government => 'Government',
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
