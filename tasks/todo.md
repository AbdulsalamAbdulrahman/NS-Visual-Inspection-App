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
- [x] Enums: Role, UserStatus, NemsaCategory (backed)
- [x] Migrations: users (uuid, phone, role, status, must_change_password, last_active_at, soft deletes), contractor_profiles, service_areas, rep_service_area
- [x] Models + factories (User, ContractorProfile, ServiceArea) with states per role/status
- [x] Fortify: custom authentication — suspended accounts get the AU-05 state, soft-deleted can't sign in
- [x] Password rules match AU-03 (8+, number, upper & lower, symbol); reset links expire in 30 min
- [x] Middleware: role, must_change_password (AU-03 first sign-in), active-account guard, last_active_at
- [x] `/` redirects by role; role route groups (admin, contractor, rep)
- [x] Pages: sign-in (incl. suspended), first-login password, forgot password (AU-04 with NSD call/email), reset password, profile (CH-03), change password
- [x] Shared `auth.user` via UserResource (initials, subtitle, badge, areas)
- [x] `app:create-admin {email} {name}` prints a temporary password
- [x] Pest: suspended can't sign in, first sign-in forces password change, role gates, create-admin, password change
- [x] Visual check of shells and auth screens vs designs; build + tests; commit

## Phase 2 · Admin management
- [x] fee_schedules table + FeeSchedule model (current / scheduled), Money helper, launch fee seeder
- [x] Actions: CreateContractor, UpdateContractor, SaveRep, ResendLoginDetails, SetSuspended, DeleteAccount
- [x] Queued LoginDetails + reset-password notifications
- [x] Contractors: list (search, summary, pagination), create/edit drawer & phone sheet, row menu & action sheet, in-page delete confirm with "Suspend instead"
- [x] Service reps: list, drawer with 1–3 area picker showing who covers each area
- [x] Service areas: add, rename inline, deactivate/reactivate
- [x] Fee settings: current card, schedule form, cancel scheduled, history
- [x] Admin nav counts + current fee shared on admin responses
- [x] Pest: contractor CRUD/validation, account actions, rep areas, areas, fee timing
- [ ] Contractor INSP. and area inspection counts (needs inspections — Phase 3/5)
