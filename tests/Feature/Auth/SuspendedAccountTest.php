<?php

declare(strict_types=1);

use App\Models\User;

test('suspended users cannot sign in and see the suspended state', function () {
    $user = User::factory()->contractor()->suspended()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('suspended');

    $this->assertGuest();
});

test('a wrong password on a suspended account does not reveal the suspension', function () {
    $user = User::factory()->contractor()->suspended()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email')->assertSessionDoesntHaveErrors('suspended');
});

test('a user suspended mid-session is signed out on their next request', function () {
    $user = User::factory()->contractor()->create();

    $this->actingAs($user)->get(route('inspections.index'))->assertOk();

    $user->update(['status' => 'suspended']);

    $this->get(route('inspections.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('suspended', true);

    $this->assertGuest();
});

test('deleted users cannot sign in', function () {
    $user = User::factory()->contractor()->create();
    $user->delete();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
