<?php

declare(strict_types=1);

namespace App\Enums;

enum InspectionStatus: string
{
    /** Created by a rep for a contractor; not started yet. */
    case Assigned = 'assigned';
    case Draft = 'draft';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
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
