<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AttachmentType;
use App\Enums\CircuitDescription;
use App\Enums\WiringMethod;
use App\Models\Inspection;
use App\Models\InspectionCircuit;

/**
 * Full validation of an inspection before payment (Review CR-01, Pay).
 * Drafts may be incomplete; this lists what still blocks submission.
 * Keep in step with resources/js/lib/inspection/checklist.ts.
 */
final class InspectionChecklist
{
    /** Soft limits: out-of-range readings warn but never block. */
    public const MIN_ELECTRODE_FT = 6.0;

    public const MIN_EARTH_CONDUCTOR_MM2 = 10.0;

    public const MAX_EARTH_RESISTANCE_OHM = 2.0;

    /** GPS fixes worse than this show the "low accuracy" state. */
    public const GOOD_GPS_ACCURACY_M = 20.0;

    /**
     * @return list<array{key: string, label: string, step: int}>
     */
    public static function missing(Inspection $inspection): array
    {
        $inspection->loadMissing(['circuits', 'attachments', 'serviceArea']);

        $missing = [];
        $need = function (bool $ok, string $key, string $label, int $step) use (&$missing): void {
            if (! $ok) {
                $missing[] = compact('key', 'label', 'step');
            }
        };
        $filled = fn (mixed $value): bool => $value !== null && $value !== '';

        // 1 · A
        $need($filled($inspection->form74_no), 'form74_no', 'Form 74 number', 1);
        $need($filled($inspection->owner_name), 'owner_name', 'Property owner name', 1);
        $need($filled($inspection->property_address), 'property_address', 'Property address', 1);
        $need($inspection->serviceArea?->is_active === true, 'service_area_id', 'Service area', 1);
        $need($inspection->purpose !== null, 'purpose', 'Purpose of property', 1);
        $need($inspection->connection_type !== null, 'connection_type', 'Connection type', 1);
        $need($inspection->voltage_level !== null, 'voltage_level', 'Voltage level', 1);
        $need($inspection->gps_lat !== null && $inspection->gps_lng !== null, 'gps', 'GPS location', 1);

        // 2 · B1
        $need($inspection->earth_electrode_ft !== null, 'earth_electrode_ft', 'Earth electrode size', 2);
        $need($inspection->earth_conductor_mm2 !== null, 'earth_conductor_mm2', 'Earth conductor size', 2);
        $need($inspection->earth_resistance_ohm !== null, 'earth_resistance_ohm', 'Earth resistance', 2);
        $need($inspection->earth_pit !== null, 'earth_pit', 'Earth inspection pit', 2);

        // 3 · B2
        $need($inspection->cb_rated_a !== null && $inspection->cb_standard !== null, 'cb', 'Circuit breaker', 3);
        $need($inspection->fuse_rated_a !== null && $inspection->fuse_standard !== null, 'fuse', 'Cut-out fuse', 3);
        $need($inspection->poles !== null, 'poles', 'Number of poles', 3);

        // 4 · B3
        $need($inspection->db_seen !== null && ($inspection->db_seen === false || $inspection->db_standard !== null), 'db', 'Distribution board', 4);
        $need($inspection->changeover_seen !== null && ($inspection->changeover_seen === false || $inspection->changeover_standard !== null), 'changeover', 'Change-over switch', 4);
        $need($inspection->main_switch_standard !== null, 'main_switch_standard', 'Main switch type', 4);
        $need($inspection->cb_type_standard !== null, 'cb_type_standard', 'Circuit breaker type', 4);
        $need(
            $inspection->secondary_rated_a !== null && $inspection->secondary_type !== null && $inspection->secondary_standard !== null,
            'secondary', 'Secondary main protection', 4,
        );
        $need($inspection->socket_outlets_standard !== null, 'socket_outlets_standard', 'Socket outlets type', 4);

        // 5 · B4
        $circuits = $inspection->circuits;
        $need($circuits->isNotEmpty(), 'circuits', 'At least one circuit', 5);
        $incomplete = $circuits->reject(fn (InspectionCircuit $c): bool => self::circuitComplete($c));
        foreach ($incomplete as $circuit) {
            $missing[] = ['key' => "circuit.{$circuit->uuid}", 'label' => "Circuit {$circuit->label()} details", 'step' => 5];
        }

        // 6 · C
        $need($inspection->earthing_system_type !== null, 'earthing_system_type', 'Earthing system type', 6);
        $need($inspection->db_count !== null, 'db_count', 'Number of distribution boards', 6);
        $need($inspection->sub_circuit_count !== null, 'sub_circuit_count', 'Number of sub-circuits', 6);
        $need($inspection->main_cable_mm2 !== null, 'main_cable_mm2', 'Main cable size', 6);
        $need($inspection->conductor_type !== null, 'conductor_type', 'Conductor type', 6);
        $need(
            $inspection->wiring_method !== null && ($inspection->wiring_method !== WiringMethod::Other || $filled($inspection->wiring_method_other)),
            'wiring_method', 'Wiring method', 6,
        );
        $need($filled($inspection->cable_insulation), 'cable_insulation', 'Cable insulation type', 6);

        // 7 · Attachments
        $types = $inspection->attachments->pluck('type');
        $need($types->contains(AttachmentType::Layout), 'layout', 'Electrical layout & load schedule', 7);
        $need($types->contains(AttachmentType::Photo), 'photos', 'Photos of DB & earthing', 7);

        // 8 · D
        $need($inspection->declaration_accepted_at !== null, 'declaration', 'Declaration', 8);
        $need($filled($inspection->signature_path), 'signature', 'Signature', 8);

        return $missing;
    }

    public static function isComplete(Inspection $inspection): bool
    {
        return self::missing($inspection) === [];
    }

    public static function circuitComplete(InspectionCircuit $circuit): bool
    {
        return $circuit->description !== null
            && ($circuit->description !== CircuitDescription::Other || filled($circuit->description_other))
            && $circuit->rating_a !== null
            && $circuit->conductor_mm2 !== null
            && $circuit->condition !== null;
    }
}
