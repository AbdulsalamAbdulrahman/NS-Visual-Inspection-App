# Decisions

Calls made where the spec left room, or where the spec and designs disagreed.

## Phase 0
- **Repo layout:** the app lives on an orphan `main` branch; `design` branch keeps the original design files. Designs copied to `docs/design/`, kickoff spec to `docs/SPEC.md`. (User's choice.)
- **Local PHP is 8.4** (production 8.5.10). Code stays within the 8.3–8.5 compatible subset; nothing 8.5-only.
- **Local DB is MySQL 9.6** (Homebrew) rather than MariaDB/MySQL 8. Only portable SQL is used; row-locking tests run against `kens_testing`.
- **Boost** configured for Claude Code only (Cursor/Grok outputs removed) and the Laravel Cloud skill dropped — we deploy to DirectAdmin, not Laravel Cloud.
- **Svelte skills** (`svelte-code-writer`, `svelte-core-bestpractices`) come from the installed Svelte Claude Code plugin rather than copies in `.claude/skills/`; same skills and autofixer.
- **Starter kit features removed:** self-registration (spec), email verification (admin-issued accounts use real addresses; not in spec), passkeys and two-factor auth (not in spec or designs), Welcome page. Profile/password settings are rebuilt to match the designs (CH-03, AM-08, SR-M4).
- **Theme:** `data-theme="light|dark"` on `<html>`, resolved from the stored preference (light / dark / auto). Tailwind's `dark:` variant keys off `data-theme=dark`. A CSS `prefers-color-scheme` fallback covers the first paint before JS.
- **Rep list has no amount column** even though SR-D1 shows one: the spec says reps never see payment data.
- **Icons:** a custom unplugin-icons collection `~icons/ms/<name>` (in `vite.config.ts`) resolves each design ligature to Material Symbols Rounded *outlined* (FILL 0, as in the designs), falling back to the rounded glyph. One import = one SVG component; an icon barrel was tried first and bundled all 93 icons into every page, so it was dropped.
- **Fonts:** `@fontsource/*/{weight}.css` (all subsets with `unicode-range`) rather than the latin-only files, so ₦ (U+20A6, latin-ext) renders in Public Sans/Plex Mono. Browsers only download the subsets a page uses.
- **Layouts per page:** each page exports its layout from `<script module>` instead of a central `layout` callback in `app.ts`, so the admin shell (and bits-ui menus) never ship to contractor pages.
- **Sign-in "remember me":** always on (hidden field). The design has no checkbox, and contractors on phones shouldn't be signed out between site visits.
- **Starter UI libraries removed:** lucide icons, svelte-sonner, tw-animate-css, shadcn `components/ui`. Kept `bits-ui` for accessible menus/dialogs/sheets, `clsx` + `tailwind-merge` for `cn()`.
- **`local` disk `serve` turned off** (Laravel 13 default is on): uploads are only reachable through our authorised controller routes.

## Phase 1
- **Password rules** follow the AU-03 checklist everywhere (8+ characters, a number, upper & lower case, a symbol) via `Password::defaults()`, in all environments. No HIBP "uncompromised" check: it isn't in the design and adds an external call on shared hosting.
- **Suspended sign-in** is only revealed after a correct password (custom `Fortify::authenticateUsing`), so AU-05 can't be used to discover accounts. Users suspended mid-session are signed out on their next request.
- **Forgot password never reveals whether an email exists** — Fortify's failed/successful reset-link responses are both bound to one response, matching AU-04 ("If … has an account"). Reset links expire in 30 minutes (AU-04 copy).
- **First sign-in** lives at `/welcome/password` (AU-03). The new password must differ from the temporary one. A password reset by email also completes first sign-in (Invited → Active).
- **Temporary passwords** look like `Kemt-4829-pqrs`: they meet the rules and avoid look-alike characters so they can be typed from an email.
- **Profile / change password** are shared pages for all roles; the layout is chosen from the signed-in role. Reps get desktop chrome from `md` (tablet design SR-T1); contractor and admin at `lg`.
- **Seeders:** `DatabaseSeeder` is production-safe (placeholder service areas only). `DemoSeeder` (design people; all demo accounts use the password in `DemoSeeder::PASSWORD`) refuses to run in production.
