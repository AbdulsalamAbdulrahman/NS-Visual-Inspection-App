import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { icons as materialSymbols } from '@iconify-json/material-symbols';
import { getIconData, iconToHTML, iconToSVG } from '@iconify/utils';
import Icons from 'unplugin-icons/vite';
import { defineConfig, lazyPlugins } from 'vite-plus';

/**
 * `~icons/ms/<name>` → Material Symbols Rounded in the outlined (FILL 0) style
 * the designs use. Names are the design ligatures with `_` → `-`
 * (e.g. `~icons/ms/check-circle`). Falls back to the filled rounded glyph when
 * no outline variant exists. One import = one icon in the bundle.
 */
function materialSymbol(name: string): string | undefined {
    const key = [`${name}-outline-rounded`, `${name}-rounded`, name].find(
        (candidate) => getIconData(materialSymbols, candidate),
    );
    const data = key ? getIconData(materialSymbols, key) : null;

    if (!data) {
        return undefined;
    }

    const { body, attributes } = iconToSVG(data);

    return iconToHTML(body, attributes);
}

const isSvelteCheck = process.argv.some((argument) =>
    argument.includes('svelte-check'),
);

if (isSvelteCheck) {
    process.env.LARAVEL_BYPASS_ENV_CHECK ??= '1';
}

export default defineConfig({
    // Versions the service worker (/sw.js?v=…) so every build installs a fresh app shell.
    define: {
        __BUILD_ID__: JSON.stringify(Date.now().toString(36)),
    },
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        svelte(),
        Icons({
            compiler: 'svelte',
            customCollections: { ms: materialSymbol },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'docs/**',
            'tasks/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'docs/**',
            'tasks/**',
            'CLAUDE.md',
            '.claude/**',
            '.mcp.json',
            'boost.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
