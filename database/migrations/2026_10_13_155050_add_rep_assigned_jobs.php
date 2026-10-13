<?php

use App\Enums\InspectionStatus;
use App\Enums\PropertyPurpose;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (stage 3): NSD reps create jobs with the customer's details
 * and assign them to a LECAN contractor in the area's state. A job is an
 * inspection with status "assigned" until the contractor starts it.
 * Also widens the purpose enum for stage 2's "Essential service".
 */
return new class extends Migration
{
    public function up(): void
    {
        $values = fn (string $enum): array => array_column($enum::cases(), 'value');

        Schema::table('inspections', function (Blueprint $table) use ($values) {
            $table->enum('status', $values(InspectionStatus::class))->default(InspectionStatus::Draft->value)->change();
            $table->enum('purpose', $values(PropertyPurpose::class))->nullable()->change();
            $table->string('owner_phone', 20)->nullable()->after('owner_name');
            $table->string('owner_email')->nullable()->after('owner_phone');
            $table->foreignId('assigned_by')->nullable()->after('contractor_id')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->index(['assigned_by', 'status']);
        });

        Schema::table('contractor_profiles', function (Blueprint $table) {
            // Where the contractor works (LECAN chapter); reps only see contractors in the area's state.
            $table->string('state', 16)->nullable()->after('firm_name');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropIndex(['assigned_by', 'status']);
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['owner_phone', 'owner_email', 'assigned_at']);
        });
    }
};
