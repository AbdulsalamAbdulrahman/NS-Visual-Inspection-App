# KENS build plan

## Phase 0 · Setup
- [x] Scaffold Laravel 13 + Svelte starter kit (Fortify auth, Pest) on orphan `main` branch
- [x] Local MySQL databases `kens` + `kens_testing`, app user `kens`
- [x] Copy designs into `docs/design/`, spec into `docs/SPEC.md`
- [x] Boost (Claude Code only) + php-qrcode; Svelte skills via the installed Svelte plugin
- [x] npm packages: signature_pad, browser-image-compression, exifr, idb, unplugin-icons, material-symbols, fontsource
- [x] Config: app name, Africa/Lagos timezone, file cache, NSD contact config, `.env.example`
- [x] Strip starter features not in spec: registration, email verification, passkeys, 2FA, welcome page, settings pages
- [x] Design tokens → Tailwind v4 `@theme` + CSS variables (light / dark / auto via `data-theme`)
- [x] Self-hosted fonts (Public Sans, IBM Plex Mono) — drop Bunny fonts
- [x] Icons via unplugin-icons (Material Symbols Rounded)
- [x] Base UI primitives (Button, Field, TextInput, PasswordInput, Avatar, Logo, MobileHeader, BottomBar, EmptyState, Toaster, UserMenu, ThemeSwitcher)
- [x] App shells: contractor (mobile + desktop header), admin (sidebar + bottom tabs), rep (header + profile menu), auth (hero/split)
- [x] Lazy-loaded Inertia pages per role (import.meta.glob)
- [x] Tests + `npm run build` pass; update CLAUDE.md status; commit

## Phase 1 · Auth and accounts
(planned when Phase 0 is done)
