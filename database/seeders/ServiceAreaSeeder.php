<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceArea;
use Illuminate\Database\Seeder;

/**
 * Placeholder areas until Kaduna Electric sends the real list; admins can
 * rename, add and deactivate them in the UI.
 */
class ServiceAreaSeeder extends Seeder
{
    public const AREAS = [
        'Barnawa', 'Kawo', 'Doka', 'Rigasa', 'Tudun Wada', 'Sabon Tasha', 'Kakuri',
        'Zaria', 'Kafanchan', 'Saminaka', 'Sokoto', 'Gusau', 'Birnin Kebbi',
    ];

    public function run(): void
    {
        foreach (self::AREAS as $name) {
            ServiceArea::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
