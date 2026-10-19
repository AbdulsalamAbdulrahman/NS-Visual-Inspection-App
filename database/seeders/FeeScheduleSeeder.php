<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Models\FeeSchedule;
use Illuminate\Database\Seeder;

/**
 * Launch fees from the day the app is first seeded: the ₦15,000 default and
 * the LECAN app fee sheet (Oct 2026). Admins change them later on the Fee
 * settings page.
 */
class FeeScheduleSeeder extends Seeder
{
    /**
     * "APP ONLINE PAYMENT" sheet, in naira per structure: stand-alone single and
     * three phase, then multiple premises single and three phase. 0 = not offered.
     *
     * @var array<string, array{0: int, 1: int, 2: int, 3: int}>
     */
    public const SHEET = [
        'single_room' => [15_000, 20_000, 7_500, 15_000],
        'two_bedroom_flat' => [15_000, 20_000, 7_500, 15_000],
        'three_bedroom_flat' => [15_000, 20_000, 7_500, 15_000],
        'duplex' => [15_000, 20_000, 7_500, 15_000],
        'block_of_flats' => [15_000, 20_000, 7_500, 15_000],
        'boys_quarters' => [15_000, 20_000, 7_500, 15_000],
        'mixed_structure' => [15_000, 20_000, 7_500, 15_000],
        'shop' => [15_000, 20_000, 7_500, 15_000],
        'plaza' => [0, 0, 5_000, 0],
        'large_plaza' => [0, 0, 4_000, 0],
        'filling_station' => [0, 25_000, 0, 0],
        'market' => [15_000, 20_000, 7_500, 15_000],
        'small_factory' => [0, 30_000, 0, 30_000],
        'medium_factory' => [0, 40_000, 0, 40_000],
        'large_factory' => [0, 60_000, 0, 0],
        'fabricators' => [15_000, 20_000, 7_500, 15_000],
        'mda' => [0, 30_000, 0, 20_000],
        'institution' => [0, 30_000, 0, 20_000],
        'offices' => [15_000, 20_000, 7_500, 15_000],
        'hospital' => [0, 35_000, 0, 20_000],
        'school' => [20_000, 30_000, 15_000, 20_000],
        'worship_centre' => [10_000, 20_000, 7_500, 15_000],
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
    public static function seedSheet(): void
    {
        $columns = [
            [ConnectionType::SinglePhase, Premises::StandAlone],
            [ConnectionType::ThreePhase, Premises::StandAlone],
            [ConnectionType::SinglePhase, Premises::Multiple],
            [ConnectionType::ThreePhase, Premises::Multiple],
        ];

        foreach (self::SHEET as $type => $amounts) {
            foreach ($columns as $i => [$connection, $premises]) {
                FeeSchedule::query()->create([
                    'building_type' => BuildingType::from($type),
                    'connection_type' => $connection,
                    'premises' => $premises,
                    'amount_kobo' => $amounts[$i] * 100,
                    'effective_from' => today(),
                    'reason' => 'LECAN fee sheet, Oct 2026',
                ]);
            }
        }
    }
}
