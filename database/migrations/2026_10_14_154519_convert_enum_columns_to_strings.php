<?php

declare(strict_types=1);

use App\Enums\AttachmentType;
use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\InspectionStatus;
use App\Enums\NemsaCategory;
use App\Enums\PaymentStatus;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database enum columns become plain strings. A MySQL enum's list is fixed when
 * its migration runs, so a new PHP case (e.g. CircuitDescription::CeilingFan)
 * was rejected in production until someone remembered a migration. Values are
 * still checked by validation (Rule::enum) and the model casts.
 */
return new class extends Migration
{
    /**
     * Table => [column, enum, nullable, default].
     *
     * @return array<string, list<array{0: string, 1: class-string<BackedEnum>, 2: bool, 3: ?string}>>
     */
    private function columns(): array
    {
        return [
            'users' => [
                ['role', Role::class, false, null],
                ['status', UserStatus::class, false, UserStatus::Invited->value],
            ],
            'payments' => [
                ['status', PaymentStatus::class, false, PaymentStatus::Pending->value],
            ],
            'inspections' => [
                ['status', InspectionStatus::class, false, InspectionStatus::Draft->value],
                ['purpose', PropertyPurpose::class, true, null],
                ['connection_type', ConnectionType::class, true, null],
                ['voltage_level', VoltageLevel::class, true, null],
                ['secondary_type', ProtectionType::class, true, null],
                ['earthing_system_type', EarthingSystemType::class, true, null],
                ['conductor_type', ConductorType::class, true, null],
                ['wiring_method', WiringMethod::class, true, null],
                ['inspector_nemsa_category', NemsaCategory::class, true, null],
            ],
            'inspection_circuits' => [
                ['description', CircuitDescription::class, true, null],
                ['condition', CircuitCondition::class, true, null],
            ],
            'inspection_attachments' => [
                ['type', AttachmentType::class, false, null],
            ],
            'contractor_profiles' => [
                ['nemsa_category', NemsaCategory::class, false, null],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as [$column, , $nullable, $default]) {
                    $definition = $blueprint->string($column, 40)->nullable($nullable);

                    if ($default !== null) {
                        $definition->default($default);
                    }

                    $definition->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Rows using a case added since (e.g. ceiling_fan) would not fit the old lists;
        // the current cases are used so the rollback can't truncate anything.
        foreach ($this->columns() as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as [$column, $enum, $nullable, $default]) {
                    $definition = $blueprint->enum($column, array_column($enum::cases(), 'value'))->nullable($nullable);

                    if ($default !== null) {
                        $definition->default($default);
                    }

                    $definition->change();
                }
            });
        }
    }
};
