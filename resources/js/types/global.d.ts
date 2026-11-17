import type { Auth } from '@/types/auth';
import type { Flash } from '@/types/ui';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            nsd: { phone: string; email: string };
            [key: string]: unknown;
        };
        flashDataType: Flash;
    }
}

declare global {
    /** Set per build in vite.config.ts; versions the service worker. */
    const __BUILD_ID__: string;
}
