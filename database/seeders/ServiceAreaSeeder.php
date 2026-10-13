<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\NigerianState;
use App\Models\ServiceArea;
use Illuminate\Database\Seeder;

/**
 * Kaduna Electric's area offices (official list from NSD, Oct 2026), shown
 * without the "AO" suffix. Admins can rename, add and deactivate them in the UI.
 * Safe to re-run: only missing areas are added.
 */
class ServiceAreaSeeder extends Seeder
{
    public const AREAS = [
        'Barnawa', 'Doka', 'Gonin Gora', 'Jaji', 'Kafanchan', 'Kawo',
        'Kebbi Central', 'Kebbi East', 'Kebbi North', 'Makera', 'Mando',
        'Millennium City', 'Rigasa', 'Sabon Gari', 'Samaru',
        'Sokoto Central', 'Sokoto East', 'Sokoto South', 'Tudun Wada',
        'Zamfara Central', 'Zamfara North', 'Zamfara West', 'Zaria City',
    ];

    public function run(): void
    {
        foreach (self::AREAS as $name) {
            $area = ServiceArea::query()->firstOrCreate(['name' => $name], ['is_active' => true, 'state' => self::stateOf($name)]);

            // Re-running fills in the state on areas created before it existed.
            $area->update(['state' => self::stateOf($name)]);
        }
    }

    /** Kebbi, Sokoto and Zamfara offices are named after their state; the rest are in Kaduna. */
    public static function stateOf(string $name): NigerianState
    {
        foreach ([NigerianState::Kebbi, NigerianState::Sokoto, NigerianState::Zamfara] as $state) {
            if (str_starts_with($name, $state->label())) {
                return $state;
            }
        }

        return NigerianState::Kaduna;
    }
}
