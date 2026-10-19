import type { Attachment, CircuitCondition } from './types';

/** LECAN (state chapter) reviews first, then NSD gives final approval. */
export type ReviewStatus =
    | 'pending_lecan'
    | 'pending_nsd'
    | 'changes_requested'
    | 'approved';

/** InspectionReportResource (read-only report for every role). */
export type Report = {
    uuid: string;
    ticketNo: string | null;
    status: 'draft' | 'submitted';
    submittedAt: string | null;
    submittedAtLong: string | null;
    a: {
        /** "084123 – 084142" for estates. */
        form74No: string | null;
        structures: number;
        ownerName: string | null;
        address: string | null;
        area: string | null;
        purpose: string | null;
        buildingType: string | null;
        connection: string | null;
        voltage: string | null;
        inspectionDate: string | null;
        contractor: string | null;
        gps: {
            lat: number;
            lng: number;
            accuracy: number | null;
            capturedAt: string | null;
            mapsUrl: string;
        } | null;
    };
    b1: {
        electrodeFt: number | null;
        conductorMm2: number | null;
        resistanceOhm: number | null;
        pit: boolean | null;
        warnings: {
            electrode: boolean;
            conductor: boolean;
            resistance: boolean;
        };
    };
    /** Circuit breaker and cut-out fuse rows, same shape as B3. */
    b2: {
        label: string;
        ref: string | null;
        value: string;
        tone: 'ok' | 'bad' | 'muted';
    }[];
    b3: {
        label: string;
        ref: string | null;
        value: string;
        tone: 'ok' | 'bad' | 'muted';
    }[];
    b4: {
        n: string;
        description: string | null;
        rating: number | null;
        conductor: number | null;
        earthConductor: number | null;
        /** "Red / Black / Green-Yellow" (live / neutral / earth). */
        colours: string | null;
        condition: CircuitCondition | null;
        observation: string | null;
    }[];
    c: {
        earthingSystem: string | null;
        dbCount: number | null;
        subCircuitCount: number | null;
        mainCableMm2: number | null;
        conductorType: string | null;
        wiringMethod: string | null;
        cableInsulation: string | null;
    };
    d: {
        name: string | null;
        category: string | null;
        regNo: string | null;
        corenNo: string | null;
        firmName: string | null;
        /** Corporate contractors: their electrical engineer/technologist. */
        engineerName: string | null;
        declaredAt: string | null;
        signatureUrl: string | null;
    };
    attachments: Attachment[];
    summary: { warnings: number; issues: number };
    review: {
        status: ReviewStatus | null;
        label: string | null;
        note: string | null;
        reviewedAt: string | null;
        approvedAt: string | null;
        /** LECAN state chapter (the area office's state). */
        chapter: string | null;
        lecanApprovedBy: string | null;
        lecanApprovedAt: string | null;
        /** Who sent it back for changes. */
        sentBackBy: 'LECAN' | 'NSD' | null;
        /** Admins and LECAN officials only. */
        history?: {
            id: number;
            action:
                | 'submitted'
                | 'changes_requested'
                | 'resubmitted'
                | 'lecan_approved'
                | 'approved';
            label: string;
            note: string | null;
            by: string | null;
            at: string;
        }[];
    };
    /** Admins only. */
    payment?: {
        reference: string;
        ourReference: string;
        amount: string;
        channel: string | null;
        paidAt: string | null;
    } | null;
};

/** Row in admin / rep inspection lists. */
export type InspectionRow = {
    uuid: string;
    ticketNo: string | null;
    ownerName: string | null;
    address: string | null;
    area: string | null;
    contractor: string | null;
    submittedAt: string | null;
    purpose: string | null;
    connection: string | null;
    review: ReviewStatus | null;
    reviewLabel: string | null;
    /** Admins only. */
    amount?: string | null;
};

export type ListFilters = {
    search: string;
    areas: number[];
    purpose: string | null;
    connection: string | null;
    from: string | null;
    to: string | null;
    review: ReviewStatus | null;
    count: number;
};

/** Review status → pill tone and label (status is never colour alone). */
export const REVIEW_TONE: Record<ReviewStatus, 'ok' | 'info' | 'imp'> = {
    pending_lecan: 'info',
    pending_nsd: 'info',
    changes_requested: 'imp',
    approved: 'ok',
};

export type FilterOptions = {
    areas: { id: number; name: string }[];
    purpose: { value: string; label: string }[];
    connection: { value: string; label: string }[];
};
