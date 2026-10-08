<?php

declare(strict_types=1);

use App\Models\FeeSchedule;
use App\Models\ServiceArea;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ServiceAreaSeeder;

test('the reference seeder adds the official area offices and the launch fee once', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(ServiceArea::query()->count())->toBe(23)
        ->and(ServiceArea::query()->where('is_active', true)->count())->toBe(23)
        ->and(ServiceArea::query()->pluck('name')->sort()->values()->all())->toBe(collect(ServiceAreaSeeder::AREAS)->sort()->values()->all())
        ->and(FeeSchedule::query()->count())->toBe(1)
        ->and(FeeSchedule::currentAmountKobo())->toBe(1_500_000)
        ->and(User::query()->count())->toBe(0);
});

test('re-seeding keeps deactivated areas and an existing fee', function () {
    ServiceArea::factory()->create(['name' => 'Barnawa', 'is_active' => false]);
    FeeSchedule::factory()->create(['amount_kobo' => 2_000_000, 'effective_from' => today()]);

    $this->seed(DatabaseSeeder::class);

    expect(ServiceArea::query()->where('name', 'Barnawa')->sole()->is_active)->toBeFalse()
        ->and(ServiceArea::query()->count())->toBe(23)
        ->and(FeeSchedule::currentAmountKobo())->toBe(2_000_000);
});

test('the area seeder on its own adds just the 23 area offices', function () {
    $this->seed(ServiceAreaSeeder::class);
    $this->seed(ServiceAreaSeeder::class);

    expect(ServiceArea::query()->count())->toBe(23)
        ->and(FeeSchedule::query()->count())->toBe(0);
});
