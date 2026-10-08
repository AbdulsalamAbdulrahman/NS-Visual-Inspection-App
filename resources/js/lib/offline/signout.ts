import { router } from '@inertiajs/svelte';
import { logout } from '@/routes';
import { clearOfflineData } from './db';

const OWNER_KEY = 'kens-offline-user';

/**
 * Sign out, then remove this app's copies from the phone (drafts, queued files,
 * saved pages). Clearing waits for the server: an offline sign-out that never
 * happened must not lose work.
 */
export function signOut(): void {
    router.post(logout.url(), {}, { onSuccess: () => void forgetDevice() });
}

async function forgetDevice(): Promise<void> {
    try {
        await clearOfflineData();
        localStorage.removeItem(OWNER_KEY);
    } catch {
        // Storage unavailable (private mode): nothing was kept anyway.
    }
}

/**
 * Called when a contractor's pages load. If a different person used this phone
 * last (e.g. their session expired instead of signing out), wipe their copies.
 */
export async function claimDevice(userUuid: string): Promise<void> {
    try {
        const previous = localStorage.getItem(OWNER_KEY);

        if (previous && previous !== userUuid) {
            await clearOfflineData();
        }

        localStorage.setItem(OWNER_KEY, userUuid);
    } catch {
        // Storage unavailable: offline copies aren't kept either.
    }
}
