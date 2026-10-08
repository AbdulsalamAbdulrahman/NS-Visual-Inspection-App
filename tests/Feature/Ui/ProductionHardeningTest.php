<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('pages carry baseline security headers', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=(), payment=(), usb=()');
});

test('unknown pages show the branded 404 page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
});

test('forbidden pages show the branded 403 page with the user still signed in', function () {
    $contractor = User::factory()->contractor()->create();
    $theirs = Inspection::factory()->submitted()->create();

    $this->actingAs($contractor)
        ->get(route('inspections.show', $theirs))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403)->where('auth.user.uuid', $contractor->uuid));
});

test('json callers keep plain json errors', function () {
    $contractor = User::factory()->contractor()->create();
    $theirs = Inspection::factory()->create();

    $this->actingAs($contractor)
        ->putJson(route('inspections.draft', $theirs->uuid), ['owner_name' => 'x'])
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

test('server errors show the branded page when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/boom', fn () => throw new RuntimeException('boom'))->middleware('web');

    $this->get('/boom')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 500));
});

test('forgot-password requests are rate limited per email', function () {
    Notification::fake();
    $user = User::factory()->contractor()->create();

    foreach (range(1, 5) as $i) {
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
    }

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 1 minute(s).']);

    // A different address from the same place still works.
    $this->post(route('password.email'), ['email' => 'someone@example.com'])->assertSessionHasNoErrors();
});

test('reset-password attempts are rate limited', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('password.update'), ['token' => 'guess-'.$i, 'email' => 'a@example.com', 'password' => 'x', 'password_confirmation' => 'x']);
    }

    $this->post(route('password.update'), ['token' => 'guess-6', 'email' => 'a@example.com', 'password' => 'x', 'password_confirmation' => 'x'])
        ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 1 minute(s).']);
});
