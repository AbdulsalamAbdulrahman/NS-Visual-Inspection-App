<?php

declare(strict_types=1);

namespace App\Enums;

enum AttachmentType: string
{
    case Layout = 'layout';
    /** The building's front, as seen from the road (client request, Oct 2026). */
    case FrontView = 'front_view';
    case Photo = 'photo';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::Layout => 'Electrical layout & load schedule',
            self::FrontView => 'House front view',
            self::Photo => 'Photo of DB & earthing',
            self::Calibration => 'Test equipment calibration certificate',
        };
    }

    /** Photos only (no PDFs). */
    public function isPhoto(): bool
    {
        return $this === self::FrontView || $this === self::Photo;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
