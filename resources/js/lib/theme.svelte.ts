import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

const STORAGE_KEY = 'appearance';

const appearance = $state<{ value: Appearance }>({ value: 'system' });

let mediaQuery: MediaQueryList | null = null;

function prefersDark(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
    );
}

function resolve(value: Appearance): ResolvedAppearance {
    return value === 'dark' || (value === 'system' && prefersDark())
        ? 'dark'
        : 'light';
}

function setCookie(value: Appearance): void {
    document.cookie = `${STORAGE_KEY}=${value};path=/;max-age=${365 * 86400};SameSite=Lax`;
}

function readStored(): Appearance {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored === 'light' || stored === 'dark' || stored === 'system') {
            return stored;
        }
    } catch {
        // Storage can be unavailable (private mode); fall back to auto.
    }

    return 'system';
}

function apply(value: Appearance): void {
    document.documentElement.dataset.theme = resolve(value);
}

function onSystemChange(): void {
    apply(appearance.value);
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    appearance.value = readStored();
    apply(appearance.value);

    mediaQuery?.removeEventListener('change', onSystemChange);
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', onSystemChange);
}

export function updateAppearance(value: Appearance): void {
    appearance.value = value;

    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Cookie below still carries the choice to the server.
    }

    setCookie(value);
    apply(value);
}

export function themeState() {
    return {
        get appearance(): Appearance {
            return appearance.value;
        },
        get resolved(): ResolvedAppearance {
            return resolve(appearance.value);
        },
        update: updateAppearance,
    };
}

export const appearanceLabels: Record<Appearance, string> = {
    light: 'Light',
    dark: 'Dark',
    system: 'Auto',
};
