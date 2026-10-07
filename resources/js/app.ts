import { createInertiaApp } from '@inertiajs/svelte';
import { initializeTheme } from '@/lib/theme.svelte';

const appName = import.meta.env.VITE_APP_NAME || 'KENS';

// Pages are resolved lazily by @inertiajs/vite (import.meta.glob, eager: false),
// and each page declares its own role layout in a module script, so a
// contractor's phone never downloads admin pages or the admin shell.
void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    progress: {
        color: '#7BB43B',
    },
});

initializeTheme();
