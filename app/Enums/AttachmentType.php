<?php

declare(strict_types=1);

namespace App\Enums;

enum AttachmentType: string
{
    case Layout = 'layout';
    case Photo = 'photo';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::Layout => 'Electrical layout & load schedule',
            self::Photo => 'Photo of DB & earthing',
            self::Calibration => 'Test equipment calibration certificate',
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
