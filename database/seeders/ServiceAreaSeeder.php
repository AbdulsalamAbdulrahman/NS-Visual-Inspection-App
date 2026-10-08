<?php

declare(strict_types=1);

namespace Database\Seeders;

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
            ServiceArea::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
