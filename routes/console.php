<?php

use Illuminate\Support\Facades\Schedule;

/*
 * One cron entry runs `schedule:run` every minute (docs/DEPLOY.md). Shared
 * hosting has no Supervisor, so the scheduler also runs the database queue
 * worker: each minute's worker polls every 3 s for 50 s, so email goes out
 * within seconds. It runs in the background so the other jobs don't wait, and
 * a failed send waits a minute before its retry.
 */
Schedule::command('queue:work --max-time=50 --sleep=3 --tries=3 --backoff=60')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// AD-09: pending > 24 h → Abandoned, after a last check with Monnify.
Schedule::command('payments:abandon-stale')
    ->hourly()
    ->withoutOverlapping(30);

// Licence expiry: one email a month before, one when it has expired.
Schedule::command('contractors:licence-check')->dailyAt('07:00');

// Bank accounts saved while Monnify was unavailable.
Schedule::command('settlements:confirm-accounts')->hourly()->withoutOverlapping(30);

// Housekeeping.
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('auth:clear-resets')->daily();
