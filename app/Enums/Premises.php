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

    public function label(): string
    {
        return match ($this) {
            self::StandAlone => 'Stand-alone',
            self::Multiple => 'Multiple premises',
        };
    }

    public static function forStructures(int $count): self
    {
        return $count > 1 ? self::Multiple : self::StandAlone;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
