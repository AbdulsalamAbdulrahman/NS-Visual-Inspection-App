<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Payments\MonnifyFake;

beforeEach(function () {
    Notification::fake();
});

function webhookBody(string $reference): string
{
    return json_encode([
        'eventType' => 'SUCCESSFUL_TRANSACTION',
        'eventData' => ['paymentReference' => $reference, 'paymentStatus' => 'PAID', 'amountPaid' => 15000],
    ]);
}

test('the webhook confirms a payment only after re-verifying with Monnify', function () {
    $payment = Payment::factory()->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference));

    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json'], content: webhookBody($payment->payment_reference))
        ->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->inspection->fresh()->ticket_no)->not->toBeNull();
});

test('a webhook claiming success is ignored when Monnify says it is still pending', function () {
    $payment = Payment::factory()->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference, 'PENDING', 0));

    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json'], content: webhookBody($payment->payment_reference))
        ->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('in production the signature must match', function () {
    $payment = Payment::factory()->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference));
    config(['services.monnify.verify_signature' => true]);
    $body = webhookBody($payment->payment_reference);

    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_MONNIFY_SIGNATURE' => 'forged'], content: $body)
        ->assertUnauthorized();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);

    $signature = hash_hmac('sha512', $body, 'TEST_SECRET');
    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_MONNIFY_SIGNATURE' => $signature], content: $body)
        ->assertOk();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('the webhook can be limited to Monnify\'s IP addresses', function () {
    $payment = Payment::factory()->create();
    MonnifyFake::fake(MonnifyFake::transaction($payment->payment_reference));
    config(['services.monnify.webhook_ips' => ['35.242.133.146']]);

    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '10.0.0.9'], content: webhookBody($payment->payment_reference))
        ->assertForbidden();
});

test('unknown references are acknowledged without action', function () {
    MonnifyFake::fake();

    $this->call('POST', route('webhooks.monnify'), server: ['CONTENT_TYPE' => 'application/json'], content: webhookBody('NOT-OURS'))
        ->assertOk()
        ->assertJson(['acknowledged' => true]);
});
