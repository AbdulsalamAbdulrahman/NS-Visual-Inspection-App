<?php

declare(strict_types=1);

use App\Models\ContractorProfile;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('contractors see their read-only licence details', function () {
    $user = User::factory()->contractor()->create(['name' => 'Engr. Yusuf Bello']);
    $user->contractorProfile->update(['nemsa_category' => 'cat_a', 'nemsa_reg_no' => 'NEMSA/A/2023/0142', 'coren_no' => 'R.12873', 'firm_name' => 'Bello Power Systems Ltd']);

    $this->actingAs($user)->get(route('profile.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Profile')
            ->where('profile.initials', 'YB')
            ->where('profile.licence.category', 'Cat A')
            ->where('profile.licence.regNo', 'NEMSA/A/2023/0142')
            ->where('auth.user.subtitle', 'Contractor · Cat A')
            ->where('auth.user.badge', 'Cat A'));
});

test('reps see their assigned areas and no licence', function () {
    $areas = ServiceArea::factory()->createMany([['name' => 'Kawo'], ['name' => 'Rigasa']]);
    $user = User::factory()->rep($areas->all())->create();

    $this->actingAs($user)->get(route('profile.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.licence', null)
            ->where('profile.areas', ['Kawo', 'Rigasa'])
            ->where('auth.user.areas', ['Kawo', 'Rigasa']));
});

test('users can change their password with the current one', function () {
    $user = User::factory()->contractor()->create();

    $this->actingAs($user)->put(route('password.change'), [
        'current_password' => 'password',
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertRedirect(route('profile.show'));

    expect(Hash::check('N3w-passw0rd!', $user->fresh()->password))->toBeTrue();
});

test('a wrong current password is rejected', function () {
    $user = User::factory()->contractor()->create();

    $this->actingAs($user)->put(route('password.change'), [
        'current_password' => 'nope',
        'password' => 'N3w-passw0rd!',
        'password_confirmation' => 'N3w-passw0rd!',
    ])->assertSessionHasErrors('current_password');
});

test('contractor profile factory makes a licence', function () {
    expect(ContractorProfile::factory()->corporate()->make()->firm_name)->toBe('Danladi Electrical Services');
});
