<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Shared hosting has no Supervisor, so the scheduler (one cron entry running
 * `schedule:run` every minute) drains the database queue: login details,
 * password resets and submission emails.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);
