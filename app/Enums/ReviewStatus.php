<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * NSD review of a submitted (paid) inspection. Drafts have no review status.
 * Only an approved inspection has a certificate.
 */
enum ReviewStatus: string
{
    case Pending = 'pending';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Under review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
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
