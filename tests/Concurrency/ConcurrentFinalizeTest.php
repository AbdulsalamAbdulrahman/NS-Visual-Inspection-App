<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\Payment;
use App\Models\TicketCounter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/*
 * Row locking only means something on a real database, so this runs against
 * MySQL/MariaDB (`kens_testing`) with genuinely parallel PHP processes.
 * Skipped when that database isn't reachable.
 */

beforeEach(function () {
    config([
        'database.default' => 'mysql',
        'database.connections.mysql.database' => env('DB_TESTING_DATABASE', 'kens_testing'),
    ]);
    DB::purge('mysql');

    try {
        DB::connection('mysql')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('MySQL test database unavailable: '.$e->getMessage());
    }

    Artisan::call('migrate:fresh', ['--force' => true]);
});

function runWorkers(array $paymentIds): array
{
    $startAt = microtime(true) + 1.5;
    $env = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => config('database.connections.mysql.database'),
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
    ];

    $results = Process::concurrently(function ($pool) use ($paymentIds, $startAt, $env) {
        foreach ($paymentIds as $id) {
            $pool->path(base_path())->env($env)->timeout(60)
                ->command([PHP_BINARY, 'tests/Concurrency/finalize-worker.php', (string) $id, (string) $startAt]);
        }
    });

    return collect($results)->map(function ($result) {
        expect($result->successful())->toBeTrue($result->errorOutput());

        return trim($result->output());
    })->all();
}

test('many payments confirmed at the same moment never share a ticket', function () {
    $payments = Payment::factory()->count(8)->create();

    $tickets = runWorkers($payments->pluck('id')->all());

    expect($tickets)->toHaveCount(8)
        ->and(array_unique($tickets))->toHaveCount(8)
        ->and(Inspection::query()->whereNotNull('ticket_no')->distinct()->count('ticket_no'))->toBe(8)
        ->and(TicketCounter::query()->sole()->last_number)->toBe(8);

    sort($tickets);
    expect($tickets[0])->toEndWith('-000001')->and($tickets[7])->toEndWith('-000008');
})->group('mysql');

test('the verify call and the webhook racing on one payment produce one ticket', function () {
    $payment = Payment::factory()->create();

    $tickets = runWorkers(array_fill(0, 6, $payment->id));

    expect(array_unique($tickets))->toHaveCount(1)
        ->and(TicketCounter::query()->sole()->last_number)->toBe(1)
        ->and($payment->fresh()->isPaid())->toBeTrue();
})->group('mysql');
