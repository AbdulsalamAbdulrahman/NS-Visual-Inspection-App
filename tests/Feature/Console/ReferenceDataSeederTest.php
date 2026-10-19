<?php

declare(strict_types=1);

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\NigerianState;
use App\Enums\Premises;
use App\Models\FeeSchedule;
use App\Models\ServiceArea;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FeeScheduleSeeder;
use Database\Seeders\ServiceAreaSeeder;

test('the reference seeder adds the official area offices and the launch fee once', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(ServiceArea::query()->count())->toBe(26)
        ->and(ServiceArea::query()->where('is_active', true)->count())->toBe(26)
        ->and(ServiceArea::query()->pluck('name')->sort()->values()->all())->toBe(collect(ServiceAreaSeeder::AREAS)->sort()->values()->all())
        ->and(FeeSchedule::query()->count())->toBe(1 + 4 * count(FeeScheduleSeeder::SHEET))
        ->and(FeeSchedule::currentAmountKobo())->toBe(1_500_000)
        ->and(FeeSchedule::forCell(BuildingType::Hospital, ConnectionType::ThreePhase, Premises::StandAlone)->amount_kobo)->toBe(3_500_000)
        ->and(User::query()->count())->toBe(0);
});

test('re-seeding keeps deactivated areas and an existing fee', function () {
    ServiceArea::factory()->create(['name' => 'Barnawa', 'is_active' => false]);
    FeeSchedule::factory()->create(['amount_kobo' => 2_000_000, 'effective_from' => today()]);

    $this->seed(DatabaseSeeder::class);

    expect(ServiceArea::query()->where('name', 'Barnawa')->sole()->is_active)->toBeFalse()
        ->and(ServiceArea::query()->count())->toBe(26)
        ->and(FeeSchedule::currentAmountKobo())->toBe(2_000_000);
});

test('the area seeder on its own adds just the 26 area offices', function () {
    $this->seed(ServiceAreaSeeder::class);
    $this->seed(ServiceAreaSeeder::class);

    expect(ServiceArea::query()->count())->toBe(26)
        ->and(FeeSchedule::query()->count())->toBe(0);
});

test('area offices get their franchise state', function () {
    $this->seed(ServiceAreaSeeder::class);

    $states = ServiceArea::query()->get()->groupBy(fn (ServiceArea $a) => $a->state->value)->map->count()->sortKeys()->all();

    expect($states)->toBe(['kaduna' => 15, 'kebbi' => 4, 'sokoto' => 4, 'zamfara' => 3])
        ->and(ServiceArea::query()->where('name', 'Zamfara West')->sole()->state)->toBe(NigerianState::Zamfara);
});
