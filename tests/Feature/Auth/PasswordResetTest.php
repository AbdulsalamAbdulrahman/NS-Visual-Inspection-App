<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\QueuedResetPassword as ResetPassword;
use Illuminate\Support\Facades\Notification;

test('a reset link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('the password can be reset with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'N3w-passw0rd!',
            'password_confirmation' => 'N3w-passw0rd!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });
});

test('the password cannot be reset with an invalid token', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertSessionHasErrors('email');
});
