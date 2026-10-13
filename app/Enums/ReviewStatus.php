<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Review of a submitted (paid) inspection. Drafts have no review status.
 * A LECAN official of the area's state chapter reviews first, then NSD gives
 * final approval; either can send it back. Only an approved inspection has
 * a certificate.
 */
enum ReviewStatus: string
{
    case PendingLecan = 'pending_lecan';
    case PendingNsd = 'pending_nsd';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::PendingLecan => 'Awaiting LECAN',
            self::PendingNsd => 'Awaiting NSD',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PendingLecan || $this === self::PendingNsd;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
