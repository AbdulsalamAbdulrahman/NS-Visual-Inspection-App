<?php

declare(strict_types=1);

use App\Enums\BuildingType;
use App\Models\FeeSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-07 10:00:00');
    $this->admin = User::factory()->admin()->create();
    FeeSchedule::factory()->create(['amount_kobo' => 1_500_000, 'effective_from' => '2026-07-01']);
});

test('the current fee is the latest one on or before today', function () {
    FeeSchedule::factory()->create(['amount_kobo' => 1_250_000, 'effective_from' => '2026-01-01']);
    FeeSchedule::factory()->create(['amount_kobo' => 1_750_000, 'effective_from' => '2027-01-01']);

    expect(FeeSchedule::currentAmountKobo())->toBe(1_500_000);
});

test('fee changes take effect from their date', function () {
    $this->actingAs($this->admin)->post(route('admin.fees.store'), [
        'amount' => '17,500',
        'effective_from' => '2027-01-01',
        'reason' => '2027 tariff review',
    ])->assertSessionHasNoErrors();

    expect(FeeSchedule::currentAmountKobo())->toBe(1_500_000);

    $this->travelTo('2026-12-31 23:59:00');
    expect(FeeSchedule::currentAmountKobo())->toBe(1_500_000);

    $this->travelTo('2027-01-01 00:00:01');
    expect(FeeSchedule::currentAmountKobo())->toBe(1_750_000);
});

test('a fee effective today applies immediately', function () {
    $this->actingAs($this->admin)->post(route('admin.fees.store'), [
        'amount' => '16000',
        'effective_from' => '2026-10-07',
    ])->assertSessionHasNoErrors();

    expect(FeeSchedule::currentAmountKobo())->toBe(1_600_000);
});

test('fees cannot be backdated or malformed', function () {
    $this->actingAs($this->admin)->post(route('admin.fees.store'), ['amount' => '17500', 'effective_from' => '2026-10-01'])
        ->assertSessionHasErrors('effective_from');

    $this->post(route('admin.fees.store'), ['amount' => 'abc', 'effective_from' => '2027-01-01'])
        ->assertSessionHasErrors('amount');
});

test('only one change can be scheduled at a time and it can be cancelled', function () {
    $this->actingAs($this->admin)->post(route('admin.fees.store'), ['amount' => '17500', 'effective_from' => '2027-01-01']);
    $this->post(route('admin.fees.store'), ['amount' => '18000', 'effective_from' => '2027-02-01'])
        ->assertSessionHasErrors('effective_from');

    $scheduled = FeeSchedule::query()->scheduled()->firstOrFail();
    $this->delete(route('admin.fees.destroy', $scheduled))->assertRedirect();

    $this->assertSoftDeleted($scheduled);
    expect(FeeSchedule::query()->scheduled()->exists())->toBeFalse();
});

test('the fee in effect cannot be cancelled', function () {
    $current = FeeSchedule::current();

    $this->actingAs($this->admin)->delete(route('admin.fees.destroy', $current))->assertStatus(422);
});

test('the fee page shows current, scheduled and history', function () {
    FeeSchedule::factory()->create(['amount_kobo' => 1_750_000, 'effective_from' => '2027-01-01']);

    $this->actingAs($this->admin)->get(route('admin.fees.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Fees')
            ->where('default.amount', '₦15,000.00')
            ->where('rows.0.key', 'default')
            ->where('rows.0.current.amount', '₦15,000.00')
            ->where('rows.0.scheduled.amount', '₦17,500.00')
            ->has('rows', 1 + count(BuildingType::cases()))
            ->where('rows.1.usesDefault', true)
            ->has('history', 2));
});

test('a building type can have its own fee; others use the default', function () {
    $this->actingAs($this->admin)->post(route('admin.fees.store'), [
        'building_type' => 'duplex',
        'amount' => '25,000',
        'effective_from' => '2026-10-07',
    ])->assertSessionHasNoErrors();

    expect(FeeSchedule::currentAmountKobo(BuildingType::Duplex))->toBe(2_500_000)
        ->and(FeeSchedule::currentAmountKobo(BuildingType::Shop))->toBe(1_500_000)
        ->and(FeeSchedule::currentAmountKobo())->toBe(1_500_000);

    // Each fee has its own schedule: a duplex change doesn't block a default change.
    $this->post(route('admin.fees.store'), ['building_type' => 'duplex', 'amount' => '26000', 'effective_from' => '2027-01-01'])->assertSessionHasNoErrors();
    $this->post(route('admin.fees.store'), ['amount' => '16000', 'effective_from' => '2027-01-01'])->assertSessionHasNoErrors();
    $this->post(route('admin.fees.store'), ['building_type' => 'duplex', 'amount' => '27000', 'effective_from' => '2027-02-01'])
        ->assertSessionHasErrors('effective_from');
});
