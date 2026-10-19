<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cable colour coding for a circuit's live, neutral and earth (LECAN, Oct 2026).
 */
enum CableColour: string
{
    case Red = 'red';
    case Yellow = 'yellow';
    case Black = 'black';
    case Blue = 'blue';
    case GreenYellow = 'green_yellow';

    public function label(): string
    {
        return match ($this) {
            self::Red => 'Red',
            self::Yellow => 'Yellow',
            self::Black => 'Black',
            self::Blue => 'Blue',
            self::GreenYellow => 'Green with yellow stripe',
        };
    }

    /** "Green-Yellow", for tables. */
    public function shortLabel(): string
    {
        return $this === self::GreenYellow ? 'Green-Yellow' : $this->label();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
