<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;

test('app:create-admin creates an invited admin with a temporary password', function () {
    $this->artisan('app:create-admin', ['email' => 'H.Musa@KadunaElectric.com', 'name' => 'Hadiza Musa'])
        ->expectsOutputToContain('Temporary password:')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'h.musa@kadunaelectric.com')->firstOrFail();

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->status)->toBe(UserStatus::Invited)
        ->and($admin->must_change_password)->toBeTrue();
});

test('app:create-admin refuses a duplicate email', function () {
    User::factory()->create(['email' => 'h.musa@kadunaelectric.com']);

    $this->artisan('app:create-admin', ['email' => 'h.musa@kadunaelectric.com', 'name' => 'Hadiza Musa'])
        ->assertFailed();
});
