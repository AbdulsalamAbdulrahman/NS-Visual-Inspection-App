<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

test('the scheduler drains the queue every minute without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'queue:work'));

    expect($event)->not->toBeNull()
        ->and($event->command)->toContain('--stop-when-empty')
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
