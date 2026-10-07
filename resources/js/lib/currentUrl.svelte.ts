import { page } from '@inertiajs/svelte';

/** Reactive pathname of the current Inertia page plus an "is active" check. */
export function currentPath() {
    const path = $derived(new URL(page.url, 'http://localhost').pathname);

    return {
        get path() {
            return path;
        },
        /** Exact match, or a sub-path of `href` when `exact` is false. */
        isActive(href: string, exact = false): boolean {
            return exact
                ? path === href
                : path === href || path.startsWith(`${href}/`);
        },
    };
}
