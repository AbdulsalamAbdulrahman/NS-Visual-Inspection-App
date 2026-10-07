<?php

declare(strict_types=1);

use App\Actions\Payments\FinalizePaidInspection;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\TicketCounter;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Payments\MonnifyFake;

beforeEach(function () {
    Notification::fake();
    $this->travelTo('2026-10-07 11:30:00');
});

test('finalising the same payment twice issues one ticket', function () {
    $payment = Payment::factory()->create();
    $finalize = app(FinalizePaidInspection::class);
    $tx = MonnifyFake::transaction($payment->payment_reference);

    $first = $finalize->handle($payment, $tx);
    $second = $finalize->handle($payment->fresh(), $tx);

    expect($first->ticket_no)->toBe('KE-NSD-2026-000001')
        ->and($second->ticket_no)->toBe('KE-NSD-2026-000001')
        ->and(TicketCounter::query()->find(2026)->last_number)->toBe(1);
});

test('ticket numbers are sequential and unique', function () {
    $finalize = app(FinalizePaidInspection::class);

    $tickets = collect(range(1, 3))->map(function () use ($finalize) {
        $payment = Payment::factory()->create();

        return $finalize->handle($payment, MonnifyFake::transaction($payment->payment_reference))->ticket_no;
    });

    expect($tickets->all())->toBe(['KE-NSD-2026-000001', 'KE-NSD-2026-000002', 'KE-NSD-2026-000003']);
});

test('numbering restarts each year', function () {
    $finalize = app(FinalizePaidInspection::class);
    $payment = Payment::factory()->create();
    $finalize->handle($payment, MonnifyFake::transaction($payment->payment_reference));

    $this->travelTo('2027-01-01 08:00:00');
    $next = Payment::factory()->create();

    expect($finalize->handle($next, MonnifyFake::transaction($next->payment_reference))->ticket_no)->toBe('KE-NSD-2027-000001');
});

test('a second payment for an already submitted inspection keeps the first ticket', function () {
    $inspection = Inspection::factory()->complete()->create();
    [$a, $b] = Payment::factory()->forInspection($inspection)->count(2)->create();
    $finalize = app(FinalizePaidInspection::class);

    $finalize->handle($a, MonnifyFake::transaction($a->payment_reference));
    $finalize->handle($b, MonnifyFake::transaction($b->payment_reference));

    expect($inspection->fresh()->ticket_no)->toBe('KE-NSD-2026-000001')
        ->and(TicketCounter::query()->find(2026)->last_number)->toBe(1)
        ->and($b->fresh()->isPaid())->toBeTrue();
});

test('a paid transaction without paidOn or with a bad date still issues the ticket', function (?string $paidOn) {
    $payment = Payment::factory()->create();
    $tx = MonnifyFake::transaction($payment->payment_reference);

    if ($paidOn === null) {
        unset($tx['paidOn']);
    } else {
        $tx['paidOn'] = $paidOn;
    }

    $inspection = app(FinalizePaidInspection::class)->handle($payment, $tx);

    expect($inspection->ticket_no)->toBe('KE-NSD-2026-000001')
        ->and($payment->fresh()->paid_at->toDateTimeString())->toBe('2026-10-07 11:30:00');
})->with(['missing' => [null], 'unparseable' => ['not a date']]);
