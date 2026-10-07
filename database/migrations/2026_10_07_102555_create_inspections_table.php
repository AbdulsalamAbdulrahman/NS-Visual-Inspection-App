<?php

declare(strict_types=1);

use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\InspectionStatus;
use App\Enums\NemsaCategory;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $values = fn (string $enum): array => array_column($enum::cases(), 'value');

        Schema::create('inspections', function (Blueprint $table) use ($values) {
            // Identity & status
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('contractor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_area_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('status', $values(InspectionStatus::class))->default(InspectionStatus::Draft->value);
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('ticket_no', 32)->nullable()->unique();

            // A · Basic customer information
            $table->string('form74_no', 60)->nullable();
            $table->string('owner_name')->nullable();
            $table->text('property_address')->nullable();
            $table->enum('purpose', $values(PropertyPurpose::class))->nullable();
            $table->enum('connection_type', $values(ConnectionType::class))->nullable();
            $table->enum('voltage_level', $values(VoltageLevel::class))->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->decimal('gps_accuracy_m', 8, 1)->nullable();
            $table->timestamp('gps_captured_at')->nullable();
            $table->date('inspection_date')->nullable();

            // B1 · Earthing system
            $table->decimal('earth_electrode_ft', 6, 2)->nullable();
            $table->decimal('earth_conductor_mm2', 6, 2)->nullable();
            $table->decimal('earth_resistance_ohm', 8, 2)->nullable();
            $table->boolean('earth_pit')->nullable();

            // B2 · Primary supply over-current protection
            $table->decimal('cb_rated_a', 7, 1)->nullable();
            $table->boolean('cb_standard')->nullable();
            $table->decimal('fuse_rated_a', 7, 1)->nullable();
            $table->boolean('fuse_standard')->nullable();
            $table->unsignedTinyInteger('poles')->nullable();

            // B3 · Mains checklist
            $table->boolean('db_seen')->nullable();
            $table->boolean('db_standard')->nullable();
            $table->boolean('changeover_seen')->nullable();
            $table->boolean('changeover_standard')->nullable();
            $table->boolean('main_switch_standard')->nullable();
            $table->boolean('cb_type_standard')->nullable();
            $table->decimal('secondary_rated_a', 7, 1)->nullable();
            $table->enum('secondary_type', $values(ProtectionType::class))->nullable();
            $table->boolean('secondary_standard')->nullable();
            $table->boolean('socket_outlets_standard')->nullable();

            // C · System details
            $table->enum('earthing_system_type', $values(EarthingSystemType::class))->nullable();
            $table->unsignedSmallInteger('db_count')->nullable();
            $table->unsignedSmallInteger('sub_circuit_count')->nullable();
            $table->decimal('main_cable_mm2', 6, 2)->nullable();
            $table->enum('conductor_type', $values(ConductorType::class))->nullable();
            $table->enum('wiring_method', $values(WiringMethod::class))->nullable();
            $table->string('wiring_method_other')->nullable();
            $table->string('cable_insulation', 120)->nullable();

            // D · Attestation (inspector details are snapshotted at submission)
            $table->timestamp('declaration_accepted_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('inspector_name')->nullable();
            $table->enum('inspector_nemsa_category', $values(NemsaCategory::class))->nullable();
            $table->string('inspector_nemsa_reg_no', 40)->nullable();
            $table->string('inspector_coren_no', 40)->nullable();
            $table->string('inspector_firm_name')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['service_area_id', 'submitted_at']);
            $table->index(['contractor_id', 'status']);
            $table->index('status');
            $table->index('form74_no');
            $table->index('owner_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
