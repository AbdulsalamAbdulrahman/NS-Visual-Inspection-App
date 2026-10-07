/** The nine steps (progress "5 / 9"). Mirrors app/Support/InspectionSteps.php. */
export const STEPS = [
    { n: 1, label: 'A · Customer information', short: 'Customer', eyebrow: 'Section A', title: 'Basic customer information' },
    { n: 2, label: 'B1 · Earthing system', short: 'Earthing', eyebrow: 'Section B · 1', title: 'Earthing system' },
    { n: 3, label: 'B2 · Over-current protection', short: 'Protection', eyebrow: 'Section B · 2', title: 'Primary supply over-current protection' },
    { n: 4, label: 'B3 · Mains checklist', short: 'Mains', eyebrow: 'Section B · 3', title: 'Mains checklist' },
    { n: 5, label: 'B4 · Description of wiring', short: 'Wiring', eyebrow: 'Section B · 4', title: 'Description of wiring' },
    { n: 6, label: 'C · System details', short: 'System', eyebrow: 'Section C', title: 'System details' },
    { n: 7, label: 'Attachments', short: 'Attachments', eyebrow: 'Attachments', title: 'Attachments' },
    { n: 8, label: 'D · Attestation', short: 'Attestation', eyebrow: 'Section D', title: 'Attestation' },
    { n: 9, label: 'Review', short: 'Review', eyebrow: 'Review', title: 'Check everything before paying' },
] as const;

export const STEP_COUNT = STEPS.length;

/** Desktop footer back label: "B3 Mains". */
export function backLabel(step: number): string {
    const s = STEPS[step - 2];

    return s ? `${s.label.split(' · ')[0]} ${s.short}` : '';
}

/** Desktop footer next label: "Next: C System details". */
export function nextLabel(step: number, compact = false): string {
    const s = STEPS[step];

    if (!s) {
        return '';
    }

    if (s.n === 9) {
        return 'Review';
    }

    return compact ? `Next: ${s.short}` : `Next: ${s.label.replace(' · ', ' ')}`;
}
