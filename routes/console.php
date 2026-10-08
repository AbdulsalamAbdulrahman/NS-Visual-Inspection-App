<?php

use Illuminate\Support\Facades\Schedule;

/*
 * One cron entry runs `schedule:run` every minute (docs/DEPLOY.md). Shared
 * hosting has no Supervisor, so the scheduler also drains the database queue:
 * login details, password resets and review emails.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// AD-09: pending > 24 h → Abandoned, after a last check with Monnify.
Schedule::command('payments:abandon-stale')
    ->hourly()
    ->withoutOverlapping(30);

// Housekeeping.
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('auth:clear-resets')->daily();
