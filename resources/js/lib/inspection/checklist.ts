import type { Circuit, Draft } from './types';

/**
 * Client mirror of app/Support/InspectionChecklist.php for the Review banner
 * and per-step checks (works offline). The server re-checks at payment.
 */
export const LIMITS = {
    minElectrodeFt: 6,
    minEarthConductorMm2: 10,
    maxEarthResistanceOhm: 2,
    goodGpsAccuracyM: 20,
} as const;

export type MissingItem = { key: string; label: string; step: number };

const filled = (v: unknown): boolean => v !== null && v !== undefined && v !== '';

export function circuitComplete(c: Circuit): boolean {
    return (
        filled(c.description) &&
        (c.description !== 'other' || filled(c.description_other?.trim())) &&
        filled(c.rating_a) &&
        filled(c.conductor_mm2) &&
        filled(c.condition)
    );
}

export function missingItems(d: Draft, activeAreaIds: Set<number>): MissingItem[] {
    const out: MissingItem[] = [];
    const need = (ok: boolean, key: string, label: string, step: number): void => {
        if (!ok) {
            out.push({ key, label, step });
        }
    };

    need(filled(d.form74_no?.trim()), 'form74_no', 'Form 74 number', 1);
    need(filled(d.owner_name?.trim()), 'owner_name', 'Property owner name', 1);
    need(filled(d.property_address?.trim()), 'property_address', 'Property address', 1);
    need(d.service_area_id !== null && activeAreaIds.has(d.service_area_id), 'service_area_id', 'Service area', 1);
    need(filled(d.purpose), 'purpose', 'Purpose of property', 1);
    need(filled(d.connection_type), 'connection_type', 'Connection type', 1);
    need(filled(d.voltage_level), 'voltage_level', 'Voltage level', 1);
    need(filled(d.gps_lat) && filled(d.gps_lng), 'gps', 'GPS location', 1);

    need(filled(d.earth_electrode_ft), 'earth_electrode_ft', 'Earth electrode size', 2);
    need(filled(d.earth_conductor_mm2), 'earth_conductor_mm2', 'Earth conductor size', 2);
    need(filled(d.earth_resistance_ohm), 'earth_resistance_ohm', 'Earth resistance', 2);
    need(filled(d.earth_pit), 'earth_pit', 'Earth inspection pit', 2);

    need(filled(d.cb_rated_a) && filled(d.cb_standard), 'cb', 'Circuit breaker', 3);
    need(filled(d.fuse_rated_a) && filled(d.fuse_standard), 'fuse', 'Cut-out fuse', 3);
    need(filled(d.poles), 'poles', 'Number of poles', 3);

    need(filled(d.db_seen) && (d.db_seen === false || filled(d.db_standard)), 'db', 'Distribution board', 4);
    need(
        filled(d.changeover_seen) && (d.changeover_seen === false || filled(d.changeover_standard)),
        'changeover',
        'Change-over switch',
        4,
    );
    need(filled(d.main_switch_standard), 'main_switch_standard', 'Main switch type', 4);
    need(filled(d.cb_type_standard), 'cb_type_standard', 'Circuit breaker type', 4);
    need(
        filled(d.secondary_rated_a) && filled(d.secondary_type) && filled(d.secondary_standard),
        'secondary',
        'Secondary main protection',
        4,
    );
    need(filled(d.socket_outlets_standard), 'socket_outlets_standard', 'Socket outlets type', 4);

    need(d.circuits.length > 0, 'circuits', 'At least one circuit', 5);
    d.circuits.forEach((c, i) => {
        if (!circuitComplete(c)) {
            out.push({ key: `circuit.${c.uuid}`, label: `Circuit C${i + 1} details`, step: 5 });
        }
    });

    need(filled(d.earthing_system_type), 'earthing_system_type', 'Earthing system type', 6);
    need(filled(d.db_count), 'db_count', 'Number of distribution boards', 6);
    need(filled(d.sub_circuit_count), 'sub_circuit_count', 'Number of sub-circuits', 6);
    need(filled(d.main_cable_mm2), 'main_cable_mm2', 'Main cable size', 6);
    need(filled(d.conductor_type), 'conductor_type', 'Conductor type', 6);
    need(
        filled(d.wiring_method) && (d.wiring_method !== 'other' || filled(d.wiring_method_other?.trim())),
        'wiring_method',
        'Wiring method',
        6,
    );
    need(filled(d.cable_insulation?.trim()), 'cable_insulation', 'Cable insulation type', 6);

    need(d.attachments.some((a) => a.type === 'layout'), 'layout', 'Electrical layout & load schedule', 7);
    need(d.attachments.some((a) => a.type === 'photo'), 'photos', 'Photos of DB & earthing', 7);

    need(d.declaration_accepted, 'declaration', 'Declaration', 8);
    need(filled(d.signature_url), 'signature', 'Signature', 8);

    return out;
}

/** Soft warnings for out-of-range readings (never block). */
export function warnings(d: Draft): Partial<Record<'electrode' | 'conductor' | 'resistance', string>> {
    return {
        electrode:
            d.earth_electrode_ft !== null && d.earth_electrode_ft < LIMITS.minElectrodeFt
                ? `Below the ${LIMITS.minElectrodeFt} ft minimum.`
                : undefined,
        conductor:
            d.earth_conductor_mm2 !== null && d.earth_conductor_mm2 < LIMITS.minEarthConductorMm2
                ? `Below the ${LIMITS.minEarthConductorMm2} mm² minimum.`
                : undefined,
        resistance:
            d.earth_resistance_ohm !== null && d.earth_resistance_ohm > LIMITS.maxEarthResistanceOhm
                ? `Above ${LIMITS.maxEarthResistanceOhm} Ω.`
                : undefined,
    };
}
