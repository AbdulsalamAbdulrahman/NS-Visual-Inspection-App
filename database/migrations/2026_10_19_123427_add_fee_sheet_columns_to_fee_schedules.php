<?php

declare(strict_types=1);

use Database\Seeders\FeeScheduleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fees per building type, supply (single / three phase) and premises
 * (stand-alone / multiple), from the LECAN app fee sheet (Oct 2026). Production
 * gets the sheet from deploy day; a fresh database (no fees yet) is left to the
 * seeders, as before. Earlier default and per-type fees stay as the fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table): void {
            $table->string('connection_type', 40)->nullable()->after('building_type');
            $table->string('premises', 40)->nullable()->after('connection_type');
            $table->index(['building_type', 'connection_type', 'premises', 'effective_from'], 'fee_schedules_cell_index');
        });

        if (DB::table('fee_schedules')->exists()) {
            FeeScheduleSeeder::seedSheet();
        }
    }

    public function down(): void
    {
        DB::table('fee_schedules')->whereNotNull('connection_type')->delete();

        Schema::table('fee_schedules', function (Blueprint $table): void {
            $table->dropIndex('fee_schedules_cell_index');
            $table->dropColumn(['connection_type', 'premises']);
        });
    }
};
