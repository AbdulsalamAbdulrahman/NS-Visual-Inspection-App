/*
 * KENS service worker (Phase 7: offline drafts).
 *
 * - App shell: precaches what the contractor app needs from the Vite manifest
 *   (the app, contractor/print/account pages and their imports, fonts, the
 *   image-compression worker); other build files are cached on first use.
 * - Contractor pages (/inspections, /inspections/{uuid}/edit) are network-first
 *   with the last copy as a fallback, for both full page loads and Inertia
 *   JSON visits. Nothing else is cached: admin and rep pages always need network.
 * - The page cache is cleared at sign-out (clearOfflineData in lib/offline/db.ts).
 *
 * Registered as /sw.js?v=<build id>, so every deploy installs a fresh shell.
 */

const VERSION = new URL(self.location.href).searchParams.get('v') || 'dev';
const SHELL = `kens-shell-${VERSION}`;
const PAGES = 'kens-pages';

const STATIC = [
    '/images/ke-logo.png',
    '/images/icon-192.png',
    '/images/icon-512.png',
    '/manifest.webmanifest',
    '/favicon.ico',
];

const PRECACHE_ENTRIES = [
    /^resources\/js\/app\.ts$/,
    /^resources\/css\/app\.css$/,
    /^resources\/js\/pages\/contractor\//,
    /^resources\/js\/pages\/account\//,
    /^resources\/js\/pages\/print\//,
    /browser-image-compression/,
];

/** Pages a contractor may need with no network. */
const OFFLINE_PAGES = [
    /^\/$/,
    /^\/inspections\/?$/,
    /^\/inspections\/[0-9a-f-]{36}\/edit$/i,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(SHELL);
            const files = new Set(STATIC);

            try {
                const response = await fetch('/build/manifest.json', {
                    cache: 'no-store',
                });
                const manifest = await response.json();
                const seen = new Set();

                const visit = (key) => {
                    const chunk = manifest[key];

                    if (!chunk || seen.has(key)) {
                        return;
                    }

                    seen.add(key);
                    files.add(`/build/${chunk.file}`);
                    (chunk.css || []).forEach((f) => files.add(`/build/${f}`));
                    (chunk.assets || []).forEach((f) =>
                        files.add(`/build/${f}`),
                    );
                    (chunk.imports || []).forEach(visit);
                };

                Object.keys(manifest)
                    .filter((key) =>
                        PRECACHE_ENTRIES.some((re) => re.test(key)),
                    )
                    .forEach(visit);
            } catch {
                // No manifest (dev server): nothing to precache beyond the statics.
            }

            // One missing file must not abort the whole install.
            await Promise.all(
                [...files].map((url) => cache.add(url).catch(() => undefined)),
            );
            await self.skipWaiting();
        })(),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const names = await caches.keys();
            await Promise.all(
                names
                    .filter((n) => n.startsWith('kens-shell-') && n !== SHELL)
                    .map((n) => caches.delete(n)),
            );
            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith('/build/') || STATIC.includes(url.pathname)) {
        event.respondWith(cacheFirst(request));

        return;
    }

    const isPage =
        request.mode === 'navigate' ||
        request.headers.get('X-Inertia') === 'true';

    if (isPage && OFFLINE_PAGES.some((re) => re.test(url.pathname))) {
        event.respondWith(networkFirst(request));
    }
});

async function cacheFirst(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok) {
        const cache = await caches.open(SHELL);
        await cache.put(request, response.clone());
    }

    return response;
}

async function networkFirst(request) {
    const cache = await caches.open(PAGES);

    try {
        const response = await fetch(request);

        // Only real pages: not redirects (e.g. to sign-in) or errors.
        if (response.ok && !response.redirected && response.type === 'basic') {
            await cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        // An Inertia visit to a page only saved as a full HTML load (e.g. home
        // after the first visit): answer with the page JSON embedded in it.
        if (request.headers.get('X-Inertia') === 'true') {
            const html = await cache.match(request.url);
            const page = html ? pageJson(await html.text()) : null;

            if (page) {
                return new Response(page, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Inertia': 'true',
                        Vary: 'X-Inertia',
                    },
                });
            }
        }

        // A full page load with no saved copy: open the last saved home page,
        // which lists drafts kept on the phone.
        if (request.mode === 'navigate') {
            const home = await cache.match('/inspections');

            if (home) {
                return home;
            }
        }

        return Response.error();
    }
}

/** The JSON Inertia embeds in the page: <script data-page="app" type="application/json">…</script>. */
function pageJson(html) {
    const match = html.match(
        /<script data-page="[^"]*" type="application\/json">([\s\S]*?)<\/script>/,
    );

    return match ? match[1] : null;
}
