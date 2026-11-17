<?php

declare(strict_types=1);

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Models\FeeSchedule;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page lists the fees in force, without unoffered or future ones', function () {
    $cell = ['building_type' => BuildingType::Duplex, 'premises' => Premises::StandAlone];

    FeeSchedule::factory()->create([...$cell, 'connection_type' => ConnectionType::SinglePhase, 'amount_kobo' => 2_000_000]);
    FeeSchedule::factory()->create([...$cell, 'connection_type' => ConnectionType::SinglePhase, 'amount_kobo' => 3_000_000]);
    FeeSchedule::factory()->create([...$cell, 'connection_type' => ConnectionType::SinglePhase, 'amount_kobo' => 9_900_000, 'effective_from' => today()->addWeek()]);
    FeeSchedule::factory()->create([...$cell, 'connection_type' => ConnectionType::ThreePhase, 'amount_kobo' => 0]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Home')
            ->has('fees', 1)
            ->where('fees.0.purpose', 'Residential')
            ->where('fees.0.rows', [[
                'building' => 'Duplex',
                'size' => 'One building',
                'single' => '₦30,000.00',
                'three' => null,
            ]])
            ->missing('operator'));
});

test('the policy pages are public', function (string $page) {
    $this->get(route('policy', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('public/Policy')->where('page', $page));
})->with(['terms', 'privacy']);

test('the home page does not name the platform operator', function () {
    $this->get(route('home'))->assertOk()->assertDontSee(config('kens.operator.name'));
});

test('the terms name the platform operator', function () {
    $this->get(route('policy', 'terms'))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('operator.name', config('kens.operator.name')));
});

test('the refund policy is hidden for now', function () {
    $this->get('/refunds')->assertNotFound();
});
