<?php

declare(strict_types=1);

use App\Enums\InspectionStatus;
use App\Enums\PaymentStatus;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\InspectionSubmitted;
use App\Support\TicketQr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Payments\MonnifyFake;

beforeEach(function () {
    $this->travelTo('2026-10-07 11:30:00');
    Notification::fake();
    FeeSchedule::factory()->create(['amount_kobo' => 1_500_000, 'effective_from' => '2026-07-01']);
    $this->contractor = User::factory()->contractor()->create();
    $this->inspection = Inspection::factory()->forContractor($this->contractor)->complete()->create();
});

test('paying starts a Monnify checkout for the fee in effect', function () {
    MonnifyFake::fake();

    $response = $this->actingAs($this->contractor)->post(route('inspections.pay.store', $this->inspection));

    $payment = Payment::query()->sole();

    $response->assertRedirect('https://sandbox.sdk.monnify.com/checkout/'.urlencode($payment->payment_reference));

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount_kobo)->toBe(1_500_000)
        ->and($payment->payment_reference)->toStartWith('KENS-20261007-')
        ->and($payment->transaction_reference)->toStartWith('MNFY|');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/init-transaction')
        && $request['amount'] == 15000
        && $request['paymentReference'] === $payment->payment_reference
        && $request['redirectUrl'] === route('payments.show', $payment)
        && $request->hasHeader('Authorization', 'Bearer test-token'));
});

test('the server re-checks the inspection before paying', function () {
    MonnifyFake::fake();
    $this->inspection->update(['signature_path' => null]);

    $this->actingAs($this->contractor)
        ->post(route('inspections.pay.store', $this->inspection))
        ->assertSessionHasErrors('inspection');

    expect(Payment::query()->count())->toBe(0);
    Http::assertNothingSent();
});

test('a contractor cannot pay for someone else\'s inspection', function () {
    MonnifyFake::fake();

    $this->actingAs(User::factory()->contractor()->create())
        ->post(route('inspections.pay.store', $this->inspection))
        ->assertForbidden();
});

test('a confirmed payment submits the inspection with a ticket and snapshot', function () {
    $payment = Payment::factory()->forInspection($this->inspection)->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference));
    $this->contractor->contractorProfile->update(['nemsa_reg_no' => 'NEMSA/A/2023/0142', 'firm_name' => 'Bello Power Systems Ltd']);

    $this->actingAs($this->contractor)
        ->postJson(route('payments.verify', $payment))
        ->assertOk()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('ticketUrl', route('inspections.ticket', $this->inspection));

    $inspection = $this->inspection->fresh();
    $payment->refresh();

    expect($inspection->status)->toBe(InspectionStatus::Submitted)
        ->and($inspection->ticket_no)->toBe('KE-NSD-2026-000001')
        ->and($inspection->submitted_at)->not->toBeNull()
        ->and($inspection->inspector_nemsa_reg_no)->toBe('NEMSA/A/2023/0142')
        ->and($inspection->inspector_firm_name)->toBe('Bello Power Systems Ltd')
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->amount_paid_kobo)->toBe(1_500_000)
        ->and($payment->channel)->toBe('ACCOUNT_TRANSFER')
        ->and($payment->transaction_reference)->toStartWith('MNFY|20261007|');

    Notification::assertSentTo($this->contractor, InspectionSubmitted::class);

    // Later profile changes don't rewrite the report.
    $this->contractor->contractorProfile->update(['firm_name' => 'Renamed Ltd']);
    expect($inspection->fresh()->inspector_firm_name)->toBe('Bello Power Systems Ltd');
});

test('overpaid counts as paid', function () {
    $payment = Payment::factory()->forInspection($this->inspection)->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference, 'OVERPAID', 15500));

    $this->actingAs($this->contractor)->postJson(route('payments.verify', $payment))->assertJsonPath('status', 'paid');
});

test('pending payments keep waiting and the draft stays a draft', function () {
    $payment = Payment::factory()->forInspection($this->inspection)->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference, 'PENDING', 0));

    $this->actingAs($this->contractor)->postJson(route('payments.verify', $payment))->assertJsonPath('status', 'pending');

    expect($this->inspection->fresh()->isDraft())->toBeTrue();
});

test('failed, expired, reversed and partly paid payments leave the draft editable', function (string $status, float $amount) {
    $payment = Payment::factory()->forInspection($this->inspection)->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference, $status, $amount));

    $this->actingAs($this->contractor)->postJson(route('payments.verify', $payment))
        ->assertJsonPath('status', 'failed')
        ->assertJsonPath('ticketUrl', null);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->inspection->fresh()->isDraft())->toBeTrue()
        ->and($this->inspection->fresh()->ticket_no)->toBeNull();
})->with([
    'failed' => ['FAILED', 0],
    'expired' => ['EXPIRED', 0],
    'reversed' => ['REVERSED', 15000],
    'partially paid' => ['PARTIALLY_PAID', 5000],
    'paid too little' => ['PAID', 14000],
]);

test('the fee is fixed when the payment starts', function () {
    MonnifyFake::fake(fn (string $reference) => MonnifyFake::transaction($reference, 'PAID', 15000));
    $this->actingAs($this->contractor)->post(route('inspections.pay.store', $this->inspection));
    $payment = Payment::query()->sole();

    FeeSchedule::factory()->create(['amount_kobo' => 2_000_000, 'effective_from' => today()]);

    $this->postJson(route('payments.verify', $payment))->assertJsonPath('status', 'paid');

    expect($payment->fresh()->amount_kobo)->toBe(1_500_000);
});

test('the return page shows processing, and sends paid payments to the ticket', function () {
    $payment = Payment::factory()->forInspection($this->inspection)->create();

    $this->actingAs($this->contractor)->get(route('payments.show', $payment))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/PaymentStatus')
            ->where('payment.status', 'pending'));

    $payment->update(['status' => PaymentStatus::Paid]);
    $this->get(route('payments.show', $payment))->assertRedirect(route('inspections.ticket', $this->inspection));

    $this->actingAs(User::factory()->contractor()->create())->get(route('payments.show', $payment))->assertForbidden();
});

test('the ticket page shows the ticket and a QR code to the verify page', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->submitted()->create(['ticket_no' => 'KE-NSD-2026-000123']);

    $this->actingAs($this->contractor)->get(route('inspections.ticket', $inspection))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/Ticket')
            ->where('ticket.ticketNo', 'KE-NSD-2026-000123')
            ->where('ticket.qr', fn (string $uri) => str_starts_with($uri, 'data:image/svg+xml;base64,')));

    expect(TicketQr::verifyUrl('KE-NSD-2026-000123'))
        ->toBe('https://kens.buildingelectcert.com.ng/verify/KE-NSD-2026-000123');
});
