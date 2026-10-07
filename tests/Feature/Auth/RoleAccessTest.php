<?php

declare(strict_types=1);

use App\Models\User;

test('home sends each role to its own landing page', function (string $state, string $route) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route('home'))->assertRedirect(route($route));
    $this->get(route($route))->assertOk();
})->with([
    'admin' => ['admin', 'admin.overview'],
    'contractor' => ['contractor', 'inspections.index'],
    'rep' => ['rep', 'rep.inspections.index'],
]);

test('roles cannot open each other\'s areas', function (string $state, array $forbidden) {
    $user = User::factory()->{$state}()->create();

    foreach ($forbidden as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
})->with([
    'contractor' => ['contractor', ['admin.overview', 'rep.inspections.index']],
    'rep' => ['rep', ['admin.overview', 'inspections.index']],
    'admin' => ['admin', ['inspections.index', 'rep.inspections.index']],
]);

test('guests are sent to sign in', function () {
    $this->get(route('admin.overview'))->assertRedirect(route('login'));
    $this->get(route('inspections.index'))->assertRedirect(route('login'));
});

test('signing in records last activity', function () {
    $user = User::factory()->contractor()->create(['last_active_at' => null]);

    $this->actingAs($user)->get(route('inspections.index'));

    expect($user->fresh()->last_active_at)->not->toBeNull();
});
