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

## Phase 2
- **Money display:** `₦15,000.00` (spec) everywhere amounts are shown, though some mockups show `₦15,000`. KPI tiles may still abbreviate (`₦1.31M`) as in AD-01.
- **Corporate NEMSA numbers** use `CP` (`NEMSA/CP/2026/0212`) as in the designs. Registration numbers are normalised to upper case and must match `NEMSA/<letters>/<year>/<digits>`.
- **Deleted accounts keep their email reserved** (unique across soft-deleted rows), so a deleted contractor can't be silently re-created; the form says so. Deleting also drops that user's sessions.
- **Delete confirmation** offers "Suspend instead" on desktop (inline row, AD-06) and phone (in-sheet step, AM-06), per the spec; the desktop mockup didn't show it.
- **Resend login details** issues a new temporary password and puts the account back to Invited (must change password); suspended accounts stay suspended.
- **Fee scheduling:** one scheduled change at a time (the AD-10 card shows a single "Scheduled" line). A change effective today applies immediately. Only future changes can be cancelled (soft delete). The launch fee is seeded at ₦15,000 from the day the app is first seeded (`FeeScheduleSeeder`).
- **Email is queued:** login details and password reset notifications implement `ShouldQueue` (database queue drained by the scheduler).
- **Contractor INSP. and area inspection counts** show 0 until inspections exist; they're wired up in Phase 3/5.

## Phase 3
- **Next on a step with gaps (CF-03):** the first tap flags the step's missing fields with the CF-03 banner; the banner offers "continue anyway" and a second tap moves on. Drafts stay incomplete-friendly (spec), and contractors without the Form 74 number on site aren't blocked. Review is the real gate.
- **Full validation = every report field.** B1–B3 and C readings, ≥1 circuit (each with description, rating, conductor, condition), layout + ≥1 photo, declaration and signature are required to pay, even though B/C labels carry no asterisk in the designs. Conditional: "Standard" only when equipment was seen; free text only for "Other".
- **One page, nine client-side steps.** Steps switch without a server round trip (works offline later). `current_step` is saved so Resume returns to where the contractor left off. `?step=N` deep-links Review "Fix" / "Edit".
- **Auto-save** sends the full draft (minus attachments) as JSON to `PUT /inspections/{uuid}/draft` ~2 s after the last change, and immediately on Save draft / close. The endpoint upserts so phone-generated uuids work offline (Phase 7). Two devices editing the same draft = last write wins.
- **Circuits** carry client uuids (unique per inspection) and are replaced as a list on each save; position gives C1, C2….
- **Signature** is a PNG data URL sent once with the next save, stored privately as `inspections/{uuid}/signature-*.png`. The pad is always dark ink on white "paper" so it stays visible in dark mode and prints correctly.
- **Attachments** upload one file per request with progress (XHR). Photos: EXIF GPS/time read with exifr first, then compressed to ~1600 px / ≤0.5 MB JPEG. Limits: layout 3, photos 12, calibration 3; server sniffs MIME and caps 10 MB.
- **GPS** watches position up to 20 s and keeps the best fix; ≤ ±20 m is accepted automatically, worse shows the low-accuracy state with "Use anyway". No map library: the captured card keeps the design's map-tile placeholder with a pin.
- **Inspection date** is the day the draft was started (on-site date), not the payment date.
- **Review with many gaps** groups the fix list by section (more than 6 items) so it stays readable; short lists show each item as in CR-01.

## Phase 4
- **Monnify endpoints** were confirmed in Oct 2026 from Monnify's API reference listings, TeamApt's Confluence and a maintained client (developers.monnify.com blocks automated fetches): login `POST /api/v1/auth/login` (Basic), init `POST /api/v1/merchant/transactions/init-transaction` (Bearer), verify `GET /api/v2/merchant/transactions/query?paymentReference=`. Re-check against the live docs when sandbox keys arrive.
- **Checkout = full-page redirect to `checkoutUrl`**, not the Web SDK modal. On phones it's more reliable: no popup/iframe blocking, works in in-app browsers, survives switching to a bank or USSD app and back, and needs no third-party script on our pages. Monnify returns the contractor to `/payments/{reference}` (our redirectUrl).
- **Return page polls our verify endpoint** every 4 s for up to 2 minutes (CP-02 copy). After that it offers "Check again" / "Back to review"; it never decides payment from the browser.
- **Paid** only when Monnify says PAID or OVERPAID **and** amountPaid ≥ the amount we stored at init. PAID-but-short is treated as failed (like PARTIALLY_PAID); the draft stays editable.
- **Webhook**: signature checked when `MONNIFY_VERIFY_SIGNATURE` is on (default: production only, since sandbox doesn't sign); optional IP allow-list via `MONNIFY_WEBHOOK_IPS`; a valid notification only triggers the same server-side verification. Unknown references are acknowledged (200) so Monnify stops retrying; a Monnify outage returns 503 so it retries.
- **Duplicate-safe finalisation**: `FinalizePaidInspection` locks the payment row, returns early if already paid, then locks the inspection and the year's ticket counter in the same transaction. The MySQL concurrency test (parallel processes) found that `insertOrIgnore` on an existing counter row deadlocks under load, so the counter row is now locked first and only inserted for the year's first ticket, and the transaction retries up to 5 times on deadlock.
- **Paid twice** (e.g. two tabs): the second payment is recorded as paid, the first ticket stands, and a warning is logged for NSD to refund manually.
- **Fee snapshot**: the amount is taken from the fee in effect when Pay is pressed and stored on the payment; later fee changes don't affect it.
- **QR code** is an SVG rendered server-side by chillerlan and sent as a data URI for an `<img>`; it encodes `https://kens.buildingelectcert.com.ng/verify/{ticket}` (configurable via `KENS_VERIFY_BASE_URL`).
