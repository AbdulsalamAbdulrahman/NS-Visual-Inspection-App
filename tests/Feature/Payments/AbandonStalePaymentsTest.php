<?php

declare(strict_types=1);

use App\Actions\Payments\VerifyPayment;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\Payment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Payments\MonnifyFake;

beforeEach(function () {
    Notification::fake();
    $this->travelTo('2026-10-08 12:00:00');
});

function pendingPayment(int $hoursAgo): Payment
{
    return Payment::factory()->forInspection(Inspection::factory()->complete()->create())->create([
        'status' => PaymentStatus::Pending,
        'created_at' => now()->subHours($hoursAgo),
    ]);
}

test('pending payments older than 24 hours that Monnify never completed are abandoned', function () {
    MonnifyFake::fake(fn (string $reference) => MonnifyFake::transaction($reference, 'PENDING', 0));
    $stale = pendingPayment(25);
    $recent = pendingPayment(3);

    $this->artisan('payments:abandon-stale')->assertSuccessful()->expectsOutputToContain('Abandoned 1');

    expect($stale->fresh()->status)->toBe(PaymentStatus::Abandoned)
        ->and($recent->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('a stale payment Monnify says was paid is finalised, not abandoned', function () {
    MonnifyFake::fake(fn (string $reference) => MonnifyFake::transaction($reference));
    $payment = pendingPayment(30);

    $this->artisan('payments:abandon-stale')->assertSuccessful()->expectsOutputToContain('found paid 1');

    $inspection = $payment->inspection->fresh();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($inspection->isSubmitted())->toBeTrue()
        ->and($inspection->ticket_no)->not->toBeNull()
        ->and($inspection->review_status)->toBe(ReviewStatus::Pending);
});

test('a payment Monnify has no record of is abandoned', function () {
    MonnifyFake::fake(null);
    $payment = pendingPayment(26);

    $this->artisan('payments:abandon-stale')->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Abandoned);
});

test('nothing is abandoned while Monnify is unreachable', function () {
    MonnifyFake::configure();
    Http::fake(['sandbox.monnify.com/*' => Http::response('Service unavailable', 503)]);
    $payment = pendingPayment(40);

    $this->artisan('payments:abandon-stale')->assertSuccessful()->expectsOutputToContain('skipped 1');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('paid, failed and already abandoned payments are left alone', function () {
    MonnifyFake::fake(fn (string $reference) => MonnifyFake::transaction($reference, 'PENDING', 0));
    $paid = Payment::factory()->create(['status' => PaymentStatus::Paid, 'created_at' => now()->subDays(3)]);
    $failed = Payment::factory()->create(['status' => PaymentStatus::Failed, 'created_at' => now()->subDays(3)]);

    $this->artisan('payments:abandon-stale')->assertSuccessful()->expectsOutputToContain('Abandoned 0');

    expect($paid->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($failed->fresh()->status)->toBe(PaymentStatus::Failed);
});

test('an abandoned payment that completes later still issues the ticket', function () {
    $payment = pendingPayment(30);
    $payment->update(['status' => PaymentStatus::Abandoned]);
    MonnifyFake::fake(fn (string $reference) => MonnifyFake::transaction($reference));

    app(VerifyPayment::class)->handle($payment->fresh());

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->inspection->fresh()->ticket_no)->not->toBeNull();
});

test('the scheduler runs the abandon check hourly', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'payments:abandon-stale'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 * * * *');
});
