<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttachmentType;
use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\InspectionStatus;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\ReviewStatus;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Defaults to an empty draft. `complete()` fills every section so it passes
 * InspectionChecklist; `submitted()` also gives it a ticket.
 *
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_id' => User::factory()->contractor(),
            'status' => InspectionStatus::Draft,
            'current_step' => 1,
            'inspection_date' => today(),
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'service_area_id' => $attributes['service_area_id'] ?? ServiceArea::factory(),
            'current_step' => 9,
            'form74_no' => 'F74/KD/2026/'.fake()->unique()->numerify('#####'),
            'owner_name' => fake()->name(),
            'property_address' => fake()->streetAddress().', Kaduna',
            'purpose' => fake()->randomElement(PropertyPurpose::cases()),
            'connection_type' => ConnectionType::ThreePhase,
            'voltage_level' => VoltageLevel::V415,
            'gps_lat' => 10.5221700,
            'gps_lng' => 7.4383100,
            'gps_accuracy_m' => 6,
            'gps_captured_at' => now(),
            'earth_electrode_ft' => 8,
            'earth_conductor_mm2' => 16,
            'earth_resistance_ohm' => 1.8,
            'earth_pit' => true,
            'cb_rated_a' => 63,
            'cb_standard' => true,
            'fuse_rated_a' => 100,
            'fuse_standard' => true,
            'poles' => 4,
            'db_seen' => true,
            'db_standard' => true,
            'changeover_seen' => false,
            'main_switch_standard' => true,
            'cb_type_standard' => true,
            'secondary_rated_a' => 40,
            'secondary_type' => ProtectionType::Rcd,
            'secondary_standard' => true,
            'socket_outlets_standard' => true,
            'earthing_system_type' => EarthingSystemType::Tt,
            'db_count' => 2,
            'sub_circuit_count' => 14,
            'main_cable_mm2' => 16,
            'conductor_type' => ConductorType::Copper,
            'wiring_method' => WiringMethod::Conduit,
            'cable_insulation' => 'PVC',
            'declaration_accepted_at' => now(),
            'signature_path' => 'inspections/test/signature.png',
        ])->afterCreating(function (Inspection $inspection): void {
            $inspection->circuits()->create([
                'uuid' => (string) Str::uuid(),
                'position' => 1,
                'description' => CircuitDescription::Lighting,
                'rating_a' => 10,
                'conductor_mm2' => 1.5,
                'condition' => CircuitCondition::Satisfactory,
            ]);

            foreach ([AttachmentType::Layout, AttachmentType::Photo] as $type) {
                $inspection->attachments()->create([
                    'type' => $type,
                    'path' => "{$inspection->storageDirectory()}/{$type->value}.jpg",
                    'original_name' => "{$type->value}.jpg",
                    'mime' => 'image/jpeg',
                    'size_bytes' => 120_000,
                ]);
            }
        });
    }

    public function submitted(): static
    {
        return $this->complete()->state(fn (): array => [
            'status' => InspectionStatus::Submitted,
            'review_status' => ReviewStatus::Pending,
            'ticket_no' => 'KE-NSD-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'submitted_at' => now(),
        ]);
    }

    /** NSD sent it back with a reason. */
    public function changesRequested(string $note = 'Earth resistance reading looks wrong; please re-test and attach the photo.'): static
    {
        return $this->submitted()->state(fn (): array => [
            'review_status' => ReviewStatus::ChangesRequested,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }

    /** Approved by NSD, with the signatory snapshot a real approval would take. */
    public function approved(): static
    {
        return $this->submitted()->state(fn (): array => [
            'review_status' => ReviewStatus::Approved,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'signatory_name' => 'Engr. Hauwa Abdullahi',
            'signatory_title' => 'Head, New Service Department',
            'signatory_signature_path' => 'inspections/test/nsd-signature.png',
        ]);
    }

    public function forContractor(User $contractor): static
    {
        return $this->state(['contractor_id' => $contractor->id]);
    }

    public function inArea(ServiceArea $area): static
    {
        return $this->state(['service_area_id' => $area->id]);
    }
}
