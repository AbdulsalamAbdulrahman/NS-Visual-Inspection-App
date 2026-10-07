export type Option<T extends string = string> = { value: T; label: string };

export type CircuitCondition = 'satisfactory' | 'improvement_required' | 'urgent_attention_required' | 'non_compliant';

export type Circuit = {
    uuid: string;
    description: string | null;
    description_other: string | null;
    rating_a: number | null;
    conductor_mm2: number | null;
    condition: CircuitCondition | null;
    observation: string | null;
};

export type AttachmentType = 'layout' | 'photo' | 'calibration';

export type Attachment = {
    uuid: string;
    type: AttachmentType;
    name: string;
    mime: string;
    size: number;
    isImage: boolean;
    url: string;
    exifLat: number | null;
    exifLng: number | null;
    takenAt: string | null;
};

/** Editable draft state; keys match the SaveDraft payload. */
export type Draft = {
    uuid: string;
    status: 'draft' | 'submitted';
    current_step: number;

    form74_no: string | null;
    owner_name: string | null;
    property_address: string | null;
    service_area_id: number | null;
    purpose: string | null;
    connection_type: string | null;
    voltage_level: string | null;
    gps_lat: number | null;
    gps_lng: number | null;
    gps_accuracy_m: number | null;
    gps_captured_at: string | null;
    inspection_date: string | null;

    earth_electrode_ft: number | null;
    earth_conductor_mm2: number | null;
    earth_resistance_ohm: number | null;
    earth_pit: boolean | null;

    cb_rated_a: number | null;
    cb_standard: boolean | null;
    fuse_rated_a: number | null;
    fuse_standard: boolean | null;
    poles: number | null;

    db_seen: boolean | null;
    db_standard: boolean | null;
    changeover_seen: boolean | null;
    changeover_standard: boolean | null;
    main_switch_standard: boolean | null;
    cb_type_standard: boolean | null;
    secondary_rated_a: number | null;
    secondary_type: string | null;
    secondary_standard: boolean | null;
    socket_outlets_standard: boolean | null;

    earthing_system_type: string | null;
    db_count: number | null;
    sub_circuit_count: number | null;
    main_cable_mm2: number | null;
    conductor_type: string | null;
    wiring_method: string | null;
    wiring_method_other: string | null;
    cable_insulation: string | null;

    declaration_accepted: boolean;
    signature_url: string | null;

    circuits: Circuit[];
    attachments: Attachment[];
    updated_at: string | null;
};

export type FormOptions = {
    purpose: Option[];
    connectionType: Option[];
    voltageLevel: Option[];
    protectionType: Option[];
    earthingSystemType: Option[];
    conductorType: Option[];
    wiringMethod: Option[];
    circuitDescription: Option[];
    circuitCondition: Option<CircuitCondition>[];
};

export type Inspector = {
    name: string;
    category: string | null;
    regNo: string | null;
    corenNo: string | null;
    firmName: string | null;
};
