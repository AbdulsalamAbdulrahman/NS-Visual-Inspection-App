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
- [x] Contractor INSP. and area inspection counts (wired in Phase 3)

## Phase 3 · Inspection form
- [x] Enums for every inspection field; inspections, inspection_circuits, inspection_attachments tables with spec indexes
- [x] Models, policies (view / update / pay / print), visibleTo scope mirrored by policy
- [x] InspectionChecklist (server) + checklist.ts (client) for full validation; soft warnings never block
- [x] StartInspection / SaveDraft (upsert, circuits replace, signature PNG) / StoreAttachment actions
- [x] Contractor home: drafts with progress + resume, submitted list with search and pagination, empty state
- [x] 9-step form: phone header + progress, desktop top bar + step rail, CF-03 step check, offline banner, autosave chip
- [x] Steps A (GPS states), B1 (warnings), B2 (stepper), B3, B4 (cards + sheet / table + inline editor), C, Attachments (EXIF + compression + progress), D (signature), Review (fix list, edit links)
- [x] Authorised attachment + signature routes
- [x] Pest: drafts, upsert, circuits, signature storage, ownership, submitted lock, attachments (MIME, size, private disk, rep/area access), checklist

## Phase 4 · Payment and ticket 
- [x] payments + ticket_counters tables, PaymentStatus enum, Payment / TicketCounter models, PaymentFactory
- [x] MonnifyClient (login token cache, init-transaction, v2 query by paymentReference) — endpoints checked Oct 2026
- [x] Actions: InitPayment, VerifyPayment, FinalizePaidInspection (lockForUpdate, idempotent, snapshot), GenerateTicket
- [x] InspectionSubmitted queued email; TicketQr (chillerlan SVG → /verify/{ticket})
- [x] PaymentController (pay page, start → Inertia::location, return page, verify JSON), MonnifyWebhookController (signature in prod, IP list, re-verify), TicketController; routes; CSRF exemption
- [x] PayPanel component, pages/contractor/Pay.svelte (CP-01)
- [x] pages/contractor/PaymentStatus.svelte (CP-02 polling ≤2 min, CP-03 failed + Try again)
- [x] pages/contractor/Ticket.svelte (CT-01 phone, CK-04 desktop)
- [x] Wire Review: phone footer → /inspections/{uuid}/pay; desktop review grid + PayPanel (CK-03)
- [x] Pest with Http::fake: init, verify paid/pending/failed/underpaid, webhook signature, idempotent finalise, sequential tickets, fee taken at init
- [x] MySQL concurrency test (Process pool on kens_testing) for duplicate-safe finalisation
- [x] Decisions (redirect checkout over Web SDK; duplicate payment keeps first ticket), build, autofixer, visual check, commit

## Phase 5 · Viewing
- [x] InspectionFilters (search, areas, purpose, connection, date range) shared by admin list, rep list and CSV export
- [x] InspectionRowResource (amount for admins only), InspectionReportResource (sections A–D, attachments, summary; payment for admins only)
- [x] Admin: inspections list (AD-02 table / AM-02 cards, filter toolbar + AM-03 sheet), detail (AD-03 / AM-04), streamed CSV export
- [x] Rep: list scoped to assigned areas (SR-D1 / SR-T1 / SR-M1, area chips with counts, SR-M2 empty state), detail (SR-M3)
- [x] Contractor: read-only report for submitted inspections (CD-01); edit redirects there; home rows link to it
- [x] Shared report components: InspectionReport, ReportPage, Lightbox (swipe), ListFilters
- [x] Admin overview (AD-01 / AM-01): month picker, KPI tiles, submissions-by-area chart, recent submissions
- [x] Admin payments (AD-09): monthly summary, status chips, search, CSV export
- [x] Admin nav inspections count
- [x] DemoSeeder: paid submissions, failed/abandoned attempts, placeholder signature and photos
- [x] Pest: rep area isolation (list, detail, attachments, signature), contractor isolation, admin filters, CSV contents/order, payments, overview, nav count
- [x] Light theme is the default (user decision); tests for the server-rendered theme
- [x] Build, types, autofixer; contractor views checked in Chrome (admin/rep visual check deferred to production testing); commit

## Deploy (pulled forward from Phase 8)
- [x] GitHub Actions: lint, PHPStan, svelte-check, Pest (+ MariaDB 11.4 row-locking tests), build assets, publish a `deploy` branch
- [x] Server pull script run by cron: release folders, composer, migrate, caches, atomic switch, lock, log, --force / --rollback (dry-run tested locally)
- [x] Scheduler drains the database queue every minute (pulled forward from Phase 8)
- [x] `deploy/env.production.example`, `docs/DEPLOY.md` one-time setup
- [x] PHPStan clean (fixed: paidOn crash when Monnify omits the date, typed request data, Monnify return shape) and `vp check` clean
- [ ] Server one-time setup (deploy key, clone, shared/.env, cron, first admin) — with the user

## Phase 6 · Review, certificate, print and verify
Client decisions (7 Oct 2026): app renamed "Building Electrical Inspection & Certification" (short name KENS stays); certificate issued only after NSD approval; signed by the contractor + Head of NSD; "Print certificate" and "Print full report" are separate actions. Certificate design follows the client's sample layout with Kaduna Electric branding — no coat of arms, NEMSA marks or federal wording.
- [x] Rename: layouts, auth shell, emails, Monnify description, CLAUDE.md
- [x] ReviewStatus enum (pending, changes_requested, approved) + review columns, approver snapshot, certificate signatory settings table
- [x] Actions: ApproveInspection (snapshot signatory), RequestChanges (reason, reopens for edits), ResubmitInspection (no new payment, back to pending)
- [x] Policy: review (admin), update while changes requested, certificate (approved + view), print (submitted + view)
- [x] Notifications: approved (certificate link), changes requested (reason) to the contractor
- [x] Admin: review panel on detail (approve / request changes), review filter + "Pending review" on list, nav count, overview tile
- [x] Contractor: review status on home + report, changes-requested banner, edit + resubmit without paying
- [x] Rep: review status visible; certificate only when approved
- [x] Settings page: NSD signatory name, title, signature image (private disk)
- [x] Full report print `/inspections/{uuid}/print` (PR-01/PR-02, no payment data) + PR-03 phone preview
- [x] Certificate `/inspections/{uuid}/certificate` (A4, one page, QR panel, two signatures, liability clause)
- [x] Public `/verify/{ticket}` (rate-limited; masked owner; review status; no other personal data)
- [x] Pest: review rules and transitions, resubmit without payment, signatory snapshot, print/certificate access by role/area/status, verify masking + rate limit, rename
- [x] Build, types, PHPStan, autofixer, decisions, CLAUDE.md status; commit
- [ ] Visual check of certificate / print / review screens (needs a signed-in session or production)

## Phase 7 · Offline (PWA)
Design: a hand-written service worker (no new dependency) + IndexedDB via `idb`. Only the contractor's own pages are ever cached; everything is cleared at sign-out.
- [x] `public/sw.js`: precache the built app from `/build/manifest.json` (versioned per build); cache-first for `/build`, fonts and images; network-first with cached fallback for contractor pages (`/inspections`, `/inspections/{uuid}/edit`, Inertia JSON and HTML); offline navigation falls back to the cached home
- [x] `manifest.webmanifest` + icons + register the worker in production builds only
- [x] `lib/offline/db.ts`: IndexedDB stores for local drafts (payload, step, dirty flag, pending signature), the upload queue (compressed file + EXIF) and the form bootstrap (areas, options, inspector, fee); everything keyed to the signed-in user
- [x] Autosave writes to IndexedDB on every change, marks clean after the server save; "Sign in to sync" when the session has expired (401/419)
- [x] Form prefers a newer unsynced local copy over the server copy when it opens
- [x] Start a new inspection offline: phone-generated uuid, client-side Inertia visit with the cached bootstrap; synced by the PUT upsert when back online
- [x] Home: local-only and unsynced drafts appear (with "On device"), Resume works offline from the local copy
- [x] Attachment queue: offline (or failed by network) files wait in IndexedDB and upload in order once the draft exists on the server; the form picks them up when they land
- [x] Background sync on reconnect and on app start: dirty drafts first, then uploads
- [x] Sign-out warns about unsynced work, then clears IndexedDB and the page caches
- [x] Decisions, build, checks, test offline in Chrome (server stopped = no network), commit
- [ ] Not yet exercised by hand: the sign-out warning with unsynced work, and a real phone in airplane mode (needs HTTPS — test on production)

## Phase 8 · Production readiness
- [x] Index review: spec indexes present; added (status, submitted_at) on inspections (replaces the single status index) and (status, paid_at) on payments; query plans checked
- [x] N+1 guard: lazy loading throws in development and tests (whole suite passes), logged in production
- [x] `payments:abandon-stale` (hourly): pending > 24 h checked with Monnify, then finalised, failed or abandoned; untouched while Monnify is down
- [x] Scheduler: queue every minute, abandon hourly, prune failed jobs and expired reset tokens daily
- [x] Branded error pages (403/404/429/500/503) for page visits; JSON callers keep JSON; expired page (419) goes back with a toast
- [x] Security headers; rate limits on forgot/reset password
- [x] `deploy.sh` superseded by the pull deploy (see Deployment decisions); `deploy/env.production.example`; DEPLOY.md: scheduled jobs, Monnify go-live, backups, monitoring
- [x] Build, checks, 182 tests; commit
