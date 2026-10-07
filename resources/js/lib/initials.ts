// Honorifics are skipped so "Engr. Yusuf Bello" becomes "YB", as in the designs.
const HONORIFICS = new Set([
    'engr',
    'dr',
    'mr',
    'mrs',
    'ms',
    'alhaji',
    'hajiya',
    'mallam',
    'chief',
    'prof',
]);

export function getInitials(fullName?: string | null): string {
    const words = (fullName ?? '')
        .trim()
        .split(/\s+/u)
        .filter(Boolean)
        .filter((word) => !HONORIFICS.has(word.replace(/\./g, '').toLowerCase()));

    if (words.length === 0) {
        return '';
    }

    const first = Array.from(words[0])[0] ?? '';
    const last = words.length > 1 ? (Array.from(words[words.length - 1])[0] ?? '') : '';

    return `${first}${last}`.toUpperCase();
}
