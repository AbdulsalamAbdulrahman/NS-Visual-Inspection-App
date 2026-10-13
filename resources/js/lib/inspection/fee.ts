/**
 * Client mirror of app/Support/Form74Range.php and InspectionFee.php, so the
 * form can count structures and price the job offline. The server re-checks.
 */
export type FeeTable = {
    /** Default fee per structure (kobo), for types without their own. */
    default: number | null;
    types: Record<string, number>;
    effectiveFrom: string | null;
};

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
        return 'The first Form 74 number must end in digits, e.g. F74/KD/2026/08841.';
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

/** "F74/KD/2026/08841 – 08860" for display (Form74Range::label). */
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

/** Fee for a building type (or the default) × structures. */
export function quote(
    fees: FeeTable,
    buildingType: string | null,
    structures: number,
): Quote | null {
    const unit =
        (buildingType ? fees.types[buildingType] : undefined) ?? fees.default;

    if (unit === null || unit === undefined) {
        return null;
    }

    return {
        amount: naira(unit * structures),
        effectiveFrom: fees.effectiveFrom ?? '',
        breakdown:
            structures > 1 ? `${structures} structures × ${naira(unit)}` : null,
    };
}
