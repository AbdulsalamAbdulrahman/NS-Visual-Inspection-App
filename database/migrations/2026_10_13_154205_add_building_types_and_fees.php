<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (stage 2): the building description decides the fee, charged
 * per structure. Estates give a Form 74 range; the count is stored with the report.
 * A fee row with no building type is the default for types without their own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('building_type', 32)->nullable()->after('purpose');
            $table->string('form74_last_no', 60)->nullable()->after('form74_no');
            $table->unsignedSmallInteger('structure_count')->default(1)->after('form74_last_no');
        });

        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->string('building_type', 32)->nullable()->after('id');
            $table->index(['building_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::table('fee_schedules', function (Blueprint $table) {
            $table->dropIndex(['building_type', 'effective_from']);
            $table->dropColumn('building_type');
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['building_type', 'form74_last_no', 'structure_count']);
        });
    }
};
