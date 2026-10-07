<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production-safe reference data. People are created with
 * `php artisan app:create-admin`; demo data lives in DemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceAreaSeeder::class,
            FeeScheduleSeeder::class,
        ]);
    }
}
