<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback, Oct 2026 (stage 1): detailed circuit breaker / cut-out fuse
 * and mains checklist fields, the state of each area office (LECAN chapter),
 * and the electrical engineer/technologist named by corporate contractors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // B2 · circuit breaker (rated current, standard, `poles` already exist)
            $table->boolean('cb_available')->nullable()->after('earth_pit');
            $table->string('cb_make', 80)->nullable()->after('cb_available');
            // B2 · cut-out fuse
            $table->boolean('fuse_available')->nullable()->after('cb_standard');
            $table->string('fuse_type', 16)->nullable()->after('fuse_rated_a');
            $table->unsignedTinyInteger('fuse_poles')->nullable()->after('fuse_standard');
            // B3 · distribution board
            $table->unsignedSmallInteger('db_mccb_count')->nullable()->after('db_standard');
            $table->string('db_make', 80)->nullable()->after('db_mccb_count');
            // B3 · change-over switch
            $table->string('changeover_phase', 16)->nullable()->after('changeover_standard');
            $table->decimal('changeover_rated_a', 6, 1)->nullable()->after('changeover_phase');
            // B3 · main switch (BS EN 60947-3)
            $table->decimal('main_switch_rated_a', 6, 1)->nullable()->after('main_switch_standard');
            $table->unsignedTinyInteger('main_switch_poles')->nullable()->after('main_switch_rated_a');
            $table->string('main_switch_phase', 16)->nullable()->after('main_switch_poles');
            // B3 · DB main breaker switch
            $table->boolean('db_main_breaker_seen')->nullable()->after('main_switch_phase');
            $table->decimal('db_main_breaker_rated_a', 6, 1)->nullable()->after('db_main_breaker_seen');
            $table->unsignedTinyInteger('db_main_breaker_poles')->nullable()->after('db_main_breaker_rated_a');
            $table->boolean('db_main_breaker_standard')->nullable()->after('db_main_breaker_poles');
            // D · snapshot for corporate contractors
            $table->string('inspector_engineer_name')->nullable()->after('inspector_firm_name');
            $table->string('inspector_engineer_reg_no', 60)->nullable()->after('inspector_engineer_name');
        });

        // Reports already filled in had a breaker and fuse whenever a rating was given.
        DB::table('inspections')->whereNotNull('cb_rated_a')->update(['cb_available' => true]);
        DB::table('inspections')->whereNotNull('fuse_rated_a')->update(['fuse_available' => true]);

        Schema::table('service_areas', function (Blueprint $table) {
            $table->string('state', 16)->default('kaduna')->after('name');
        });

        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->string('engineer_name')->nullable()->after('firm_name');
            $table->string('engineer_reg_no', 60)->nullable()->after('engineer_name');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->dropColumn(['engineer_name', 'engineer_reg_no']);
        });

        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'cb_available', 'cb_make', 'fuse_available', 'fuse_type', 'fuse_poles',
                'db_mccb_count', 'db_make', 'changeover_phase', 'changeover_rated_a',
                'main_switch_rated_a', 'main_switch_poles', 'main_switch_phase',
                'db_main_breaker_seen', 'db_main_breaker_rated_a', 'db_main_breaker_poles', 'db_main_breaker_standard',
                'inspector_engineer_name', 'inspector_engineer_reg_no',
            ]);
        });
    }
};
