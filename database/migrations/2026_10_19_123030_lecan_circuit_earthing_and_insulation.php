<?php

declare(strict_types=1);

use App\Enums\CableInsulation;
use App\Enums\InspectionStatus;
use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LECAN corrections (Oct 2026): each circuit records its earthing cable size and
 * the live / neutral / earth colours; cable insulation becomes a PVC-only choice.
 * Reports the contractor can still edit get "pvc" (anything else is cleared to be
 * re-picked); submitted reports keep the text they were filed with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_circuits', function (Blueprint $table): void {
            $table->decimal('earth_conductor_mm2', 6, 2)->nullable()->after('conductor_mm2');
            $table->string('live_colour', 40)->nullable()->after('earth_conductor_mm2');
            $table->string('neutral_colour', 40)->nullable()->after('live_colour');
            $table->string('earth_colour', 40)->nullable()->after('neutral_colour');
        });

        $editable = fn (Builder $query): Builder => $query
            ->whereIn('status', [InspectionStatus::Assigned->value, InspectionStatus::Draft->value])
            ->orWhere(fn (Builder $q): Builder => $q->where('status', InspectionStatus::Submitted->value)
                ->where('review_status', ReviewStatus::ChangesRequested->value));

        DB::table('inspections')->where($editable)->update([
            'cable_insulation' => DB::raw("CASE WHEN UPPER(REPLACE(TRIM(cable_insulation), ' ', '')) = 'PVC' OR cable_insulation IS NULL OR TRIM(cable_insulation) = '' THEN '".CableInsulation::Pvc->value."' ELSE NULL END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('inspection_circuits', function (Blueprint $table): void {
            $table->dropColumn(['earth_conductor_mm2', 'live_colour', 'neutral_colour', 'earth_colour']);
        });
    }
};
