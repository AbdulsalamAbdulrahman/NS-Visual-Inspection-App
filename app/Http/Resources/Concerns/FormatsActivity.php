<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

/**
 * "Today, 11:34" / "Yesterday" / "29 Sep" / "Never" — the LAST ACTIVE column.
 */
trait FormatsActivity
{
    protected function lastActiveLabel(): string
    {
        $at = $this->last_active_at;

        return match (true) {
            $at === null => 'Never',
            $at->isToday() => 'Today, '.$at->format('H:i'),
            $at->isYesterday() => 'Yesterday',
            $at->isCurrentYear() => $at->format('j M'),
            default => $at->format('j M Y'),
        };
    }
}
