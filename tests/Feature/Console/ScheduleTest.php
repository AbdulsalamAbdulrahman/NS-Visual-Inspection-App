<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

test('the scheduler keeps a queue worker polling in the background every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'queue:work'));

    expect($event)->not->toBeNull()
        ->and($event->command)->not->toContain('--stop-when-empty')
        ->and($event->command)->toContain('--max-time=50')
        ->and($event->command)->toContain('--sleep=3')
        ->and($event->command)->toContain('--backoff=60')
        ->and($event->runInBackground)->toBeTrue()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
