<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Models\FeeSchedule;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Launch fees from the day the app is first seeded: the ₦15,000 default and
 * the official fee sheet (Nov 2026). Admins change them later on the Fee
 * settings page.
 */
class FeeScheduleSeeder extends Seeder
{
    /**
     * "CONTRACTOR CHARGES" sheet (official fee), in naira per structure: columns
     * are single and three phase, stand-alone then multiple, then above 15
     * (blocks of flats only). 1–4 flats and 1–5 shops use the stand-alone
     * columns, 5–15 flats and plazas the multiple ones. 0 = not offered.
     *
     * @var array<string, array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int}>
     */
    public const SHEET = [
        'single_room' => [15_000, 25_000, 0, 0, 0, 0],
        'two_bedroom_flat' => [25_000, 35_000, 15_000, 25_000, 10_000, 25_000],
        'three_bedroom_flat' => [25_000, 35_000, 15_000, 25_000, 10_000, 25_000],
        'duplex' => [30_000, 45_000, 0, 0, 0, 0],
        'block_of_flats' => [25_000, 35_000, 15_000, 25_000, 10_000, 25_000],
        'boys_quarters' => [15_000, 25_000, 0, 0, 0, 0],
        'mixed_structure' => [25_000, 35_000, 0, 0, 0, 0],
        'shop' => [15_000, 25_000, 0, 0, 0, 0],
        'plaza' => [0, 0, 10_000, 15_000, 0, 0],
        'large_plaza' => [0, 0, 7_000, 10_000, 0, 0],
        'filling_station' => [0, 50_000, 0, 0, 0, 0],
        'market' => [30_000, 45_000, 0, 0, 0, 0],
        'small_factory' => [0, 50_000, 0, 0, 0, 0],
        'medium_factory' => [0, 75_000, 0, 0, 0, 0],
        'large_factory' => [0, 100_000, 0, 0, 0, 0],
        'fabricators' => [25_000, 35_000, 15_000, 25_000, 0, 0],
        'mda' => [0, 50_000, 0, 35_000, 0, 0],
        'institution' => [0, 50_000, 0, 35_000, 0, 0],
        'offices' => [25_000, 35_000, 15_000, 25_000, 0, 0],
        'hospital' => [0, 50_000, 0, 0, 0, 0],
        'school' => [30_000, 45_000, 20_000, 30_000, 0, 0],
        'worship_centre' => [25_000, 35_000, 15_000, 25_000, 0, 0],
    ];

    public function run(): void
    {
        if (FeeSchedule::withTrashed()->exists()) {
            return;
        }

        FeeSchedule::query()->create([
            'amount_kobo' => 1_500_000,
            'effective_from' => today(),
            'reason' => 'Launch fee',
        ]);

        self::seedSheet();
    }

    /** Every cell of the sheet, effective today ("not offered" cells as ₦0). */
    public static function seedSheet(?CarbonInterface $from = null): void
    {
        $columns = [
            [ConnectionType::SinglePhase, Premises::StandAlone],
            [ConnectionType::ThreePhase, Premises::StandAlone],
            [ConnectionType::SinglePhase, Premises::Multiple],
            [ConnectionType::ThreePhase, Premises::Multiple],
            [ConnectionType::SinglePhase, Premises::Large],
            [ConnectionType::ThreePhase, Premises::Large],
        ];

        foreach (self::SHEET as $type => $amounts) {
            foreach ($columns as $i => [$connection, $premises]) {
                FeeSchedule::query()->create([
                    'building_type' => BuildingType::from($type),
                    'connection_type' => $connection,
                    'premises' => $premises,
                    'amount_kobo' => $amounts[$i] * 100,
                    'effective_from' => $from ?? today(),
                    'reason' => 'Official fee sheet, Nov 2026',
                ]);
            }
        }
    }
}
