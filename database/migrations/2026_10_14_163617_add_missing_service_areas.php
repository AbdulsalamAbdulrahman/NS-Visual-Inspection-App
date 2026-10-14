<?php

declare(strict_types=1);

use Database\Seeders\ServiceAreaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the area offices missing from the first list (Kebbi South, Saminaka,
 * Sokoto North) and fills every area's state, so production gets them on
 * deploy without running the seeder by hand. The seeder only adds missing
 * areas and never reactivates or renames. A fresh database (no areas yet)
 * is left to the seeders, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('service_areas')->doesntExist()) {
            return;
        }

        (new ServiceAreaSeeder)->run();
    }

    public function down(): void
    {
        // Areas may already have reps and inspections; leave them.
    }
};
