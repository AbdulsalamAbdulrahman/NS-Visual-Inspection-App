/**
 * Client mirror of app/Support/Form74Range.php and InspectionFee.php, so the
 * form can count structures and price the job offline. The server re-checks.
 */
export type FeeTable = {
    /** Default fee per structure (kobo), for types without their own. */
    default: number | null;
    types: Record<string, number>;
    /** Fee sheet cells, "type|supply|premises" → kobo per structure; 0 = not offered. */
    cells?: Record<string, number>;
    effectiveFrom: string | null;
};

export const NOT_OFFERED =
    'No fee is set for this building type and supply. Contact the New Service Department.';

export const MAX_STRUCTURES = 500;

/** Number of structures, or a message saying what's wrong with the range. */
export function form74Count(
    first: string | null,
    last: string | null,
): number | string {
    const a = (first ?? '').trim().match(/^(.*?)(\d+)$/);
    const end = (last ?? '').trim();

    if (end === '') {
        return 1;
    }

    if (!a) {
        return 'The first Form 74 number must end in digits, e.g. 084123.';
    }

    const b = end.includes('/')
        ? end.match(/^(.*?)(\d+)$/)
        : /^\d+$/.test(end)
          ? [end, a[1], end]
          : null;

    if (!b || b[1].toLowerCase() !== a[1].toLowerCase()) {
        return `The last Form 74 number must have the same prefix as the first (${a[1]}…).`;
    }

    const count = Number(b[2]) - Number(a[2]) + 1;

    if (count < 2) {
        return 'The last Form 74 number must be after the first.';
    }

    return count > MAX_STRUCTURES
        ? `That range covers more than ${MAX_STRUCTURES} structures. Check the numbers.`
        : count;
}

/** "084123 – 084142" for display (Form74Range::label). */
export function form74Label(first: string | null, last: string | null): string {
    if (!first) {
        return '';
    }

    if (!last) {
        return first;
    }

    return `${first} – ${last.match(/(\d+)$/)?.[1] ?? last}`;
}

export function naira(kobo: number): string {
    return `₦${(kobo / 100).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export type Quote = {
    amount: string;
    effectiveFrom: string;
    breakdown: string | null;
};

/** Per-structure fee in kobo: the sheet's cell, else the type's own, else the default. */
function unitFee(
    fees: FeeTable,
    buildingType: string | null,
    connectionType: string | null,
    structures: number,
): number | null {
    const premises = structures > 1 ? 'multiple' : 'stand_alone';
    const cell =
        buildingType && connectionType
            ? fees.cells?.[`${buildingType}|${connectionType}|${premises}`]
            : undefined;

    return (
        cell ??
        (buildingType ? fees.types[buildingType] : undefined) ??
        fees.default
    );
}

/** True when the sheet says this building and supply isn't offered (₦0). */
export function notOffered(
    fees: FeeTable,
    buildingType: string | null,
    connectionType: string | null,
    structures: number,
): boolean {
    return unitFee(fees, buildingType, connectionType, structures) === 0;
}

/** Fee for the building × structures (InspectionFee::quote). */
export function quote(
    fees: FeeTable,
    buildingType: string | null,
    connectionType: string | null,
    structures: number,
): Quote | null {
    const unit = unitFee(fees, buildingType, connectionType, structures);

    if (unit === null || unit === undefined || unit === 0) {
        return null;
    }

    return {
        amount: naira(unit * structures),
        effectiveFrom: fees.effectiveFrom ?? '',
        breakdown:
            structures > 1 ? `${structures} structures × ${naira(unit)}` : null,
    };
}
