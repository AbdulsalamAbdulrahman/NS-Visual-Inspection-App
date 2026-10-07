import { cn } from '@/lib/utils';

export type InputSize = 'lg' | 'md';

/**
 * Shared field box styling (01 Foundations): 56 px on contractor screens,
 * 52 px on desktop admin forms. Focus thickens the border to the primary
 * colour; errors use a 2 px red border.
 */
export function fieldBox(
    size: InputSize,
    invalid: boolean,
    extra?: string,
): string {
    return cn(
        'flex w-full items-center rounded-xl border-[1.5px] bg-sf transition-colors',
        'focus-within:border-2 focus-within:border-pri',
        size === 'lg' ? 'h-14 text-[17px]' : 'h-[52px] text-base',
        invalid ? 'border-2 border-bad' : 'border-line',
        extra,
    );
}

/** The bare <input> that sits inside a field box. */
export const bareInput =
    'h-full min-w-0 flex-1 self-stretch bg-transparent px-3.5 text-ink outline-none focus-visible:outline-none';
