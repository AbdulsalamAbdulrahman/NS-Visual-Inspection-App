<?php

declare(strict_types=1);

namespace App\Http\Requests\Inspections;

use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Auto-save / Save draft. Everything is optional: drafts may be incomplete.
 * Only shape and plausible ranges are checked here; completeness is
 * InspectionChecklist's job at Review and payment.
 */
class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isContractor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reading = ['nullable', 'numeric', 'min:0', 'max:100000'];
        $flag = ['nullable', 'boolean'];

        return [
            'current_step' => ['sometimes', 'integer', 'between:1,'.Inspection::STEPS],

            'form74_no' => ['nullable', 'string', 'max:60'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'property_address' => ['nullable', 'string', 'max:1000'],
            'service_area_id' => ['nullable', 'integer', Rule::exists('service_areas', 'id')],
            'purpose' => ['nullable', Rule::enum(PropertyPurpose::class)],
            'connection_type' => ['nullable', Rule::enum(ConnectionType::class)],
            'voltage_level' => ['nullable', Rule::enum(VoltageLevel::class)],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:gps_lng'],
            'gps_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:gps_lat'],
            'gps_accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'gps_captured_at' => ['nullable', 'date'],

            'earth_electrode_ft' => $reading,
            'earth_conductor_mm2' => $reading,
            'earth_resistance_ohm' => $reading,
            'earth_pit' => $flag,

            'cb_rated_a' => $reading,
            'cb_standard' => $flag,
            'fuse_rated_a' => $reading,
            'fuse_standard' => $flag,
            'poles' => ['nullable', 'integer', 'between:1,8'],

            'db_seen' => $flag,
            'db_standard' => $flag,
            'changeover_seen' => $flag,
            'changeover_standard' => $flag,
            'main_switch_standard' => $flag,
            'cb_type_standard' => $flag,
            'secondary_rated_a' => $reading,
            'secondary_type' => ['nullable', Rule::enum(ProtectionType::class)],
            'secondary_standard' => $flag,
            'socket_outlets_standard' => $flag,

            'earthing_system_type' => ['nullable', Rule::enum(EarthingSystemType::class)],
            'db_count' => ['nullable', 'integer', 'between:0,999'],
            'sub_circuit_count' => ['nullable', 'integer', 'between:0,9999'],
            'main_cable_mm2' => $reading,
            'conductor_type' => ['nullable', Rule::enum(ConductorType::class)],
            'wiring_method' => ['nullable', Rule::enum(WiringMethod::class)],
            'wiring_method_other' => ['nullable', 'string', 'max:255'],
            'cable_insulation' => ['nullable', 'string', 'max:120'],

            'declaration_accepted' => ['sometimes', 'boolean'],
            // PNG data URL from signature_pad, or null to clear.
            'signature' => ['sometimes', 'nullable', 'string', 'max:700000', 'starts_with:data:image/png;base64,'],

            'circuits' => ['sometimes', 'array', 'max:80'],
            'circuits.*.uuid' => ['required', 'uuid', 'distinct'],
            'circuits.*.description' => ['nullable', Rule::enum(CircuitDescription::class)],
            'circuits.*.description_other' => ['nullable', 'string', 'max:255'],
            'circuits.*.rating_a' => $reading,
            'circuits.*.conductor_mm2' => $reading,
            'circuits.*.condition' => ['nullable', Rule::enum(CircuitCondition::class)],
            'circuits.*.observation' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
