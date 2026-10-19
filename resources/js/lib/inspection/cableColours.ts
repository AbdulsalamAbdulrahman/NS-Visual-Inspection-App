import type { CableColour, Circuit } from './types';

/**
 * Cable colour coding (CableColour). The swatch is a cable colour, not a theme
 * colour, so it is a fixed value; it always sits next to the text label.
 */
export const CABLE_COLOURS: Record<
    CableColour,
    { short: string; swatch: string }
> = {
    red: { short: 'Red', swatch: '#D32F2F' },
    yellow: { short: 'Yellow', swatch: '#F2C200' },
    black: { short: 'Black', swatch: '#1E1E1E' },
    blue: { short: 'Blue', swatch: '#1E5BC6' },
    green_yellow: {
        short: 'Green-Yellow',
        swatch: 'repeating-linear-gradient(135deg, #2E8B3A 0 5px, #F2C200 5px 8px)',
    },
};

/** Usual colours for a new single-phase circuit. */
export const DEFAULT_COLOURS = {
    live_colour: 'red',
    neutral_colour: 'black',
    earth_colour: 'green_yellow',
} as const satisfies Partial<Circuit>;

/** "Red / Black / Green-Yellow" (live / neutral / earth). */
export function colourSummary(c: Circuit): string {
    return [c.live_colour, c.neutral_colour, c.earth_colour]
        .map((v) => (v ? CABLE_COLOURS[v].short : '—'))
        .join(' / ');
}
