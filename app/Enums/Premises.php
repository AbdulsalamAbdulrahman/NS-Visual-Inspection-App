<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fee sheet columns: one structure, or several under one Form 74 range
 * (charged per structure).
 */
enum Premises: string
{
    case StandAlone = 'stand_alone';
    case Multiple = 'multiple';
    case Large = 'large';

    public function label(): string
    {
        return match ($this) {
            self::StandAlone => 'Stand-alone',
            self::Multiple => 'Multiple premises',
            self::Large => 'Above 15',
        };
    }

    /** The sheet's column for a building: its size bands decide, not just one or many. */
    public static function forBuilding(?BuildingType $type, int $count): self
    {
        return match ($type) {
            BuildingType::BlockOfFlats, BuildingType::TwoBedroomFlat, BuildingType::ThreeBedroomFlat => match (true) {
                $count <= 4 => self::StandAlone,
                $count <= 15 => self::Multiple,
                default => self::Large,
            },
            BuildingType::Shop => $count <= 5 ? self::StandAlone : self::Multiple,
            BuildingType::Plaza, BuildingType::LargePlaza => self::Multiple,
            default => $count > 1 ? self::Multiple : self::StandAlone,
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
