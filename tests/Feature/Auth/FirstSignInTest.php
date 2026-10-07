<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('first sign-in forces a password change before anything else', function () {
    $user = User::factory()->contractor()->invited()->create(['name' => 'Engr. Yusuf Bello']);

    $this->actingAs($user)
        ->get(route('inspections.index'))
        ->assertRedirect(route('password.first'));

    $this->get(route('profile.show'))->assertRedirect(route('password.first'));

    $this->get(route('password.first'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/FirstPassword')
            ->where('firstName', 'Yusuf'));
});

test('setting a new password activates the account', function () {
    $user = User::factory()->contractor()->invited()->create();

    $this->actingAs($user)->put(route('password.first.update'), [
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertRedirect(route('home'));

    $user->refresh();

    expect($user->must_change_password)->toBeFalse()
        ->and($user->status)->toBe(UserStatus::Active)
        ->and(Hash::check('N3w-passw0rd!', $user->password))->toBeTrue();

    $this->get(route('inspections.index'))->assertOk();
});

test('the new password must meet the rules and differ from the temporary one', function () {
    $user = User::factory()->contractor()->invited()->create(['password' => 'Temp-2345-pass']);

    $this->actingAs($user)->put(route('password.first.update'), [
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ])->assertSessionHasErrors('password');

    $this->put(route('password.first.update'), [
        'password' => 'Temp-2345-pass',
        'password_confirmation' => 'Temp-2345-pass',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

test('users who already set a password are sent home from the first-sign-in page', function () {
    $user = User::factory()->contractor()->create();

    $this->actingAs($user)->get(route('password.first'))->assertRedirect(route('home'));
    $this->put(route('password.first.update'), [
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertForbidden();
});

test('resetting the password by email also completes first sign-in', function () {
    $user = User::factory()->contractor()->invited()->create();
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertSessionHasNoErrors();

    expect($user->fresh())
        ->must_change_password->toBeFalse()
        ->status->toBe(UserStatus::Active);
});
