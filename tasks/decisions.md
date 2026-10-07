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

## Phase 5
- **Lists show submitted inspections only.** Drafts never appear on admin or rep lists or details (admin detail 404s, rep detail 403s); the overview's "Drafts in progress" tile is the only place drafts are counted.
- **Filters live in the query string** (`search`, `areas` as comma ids, `purpose`, `connection`, `from`, `to`) so filtered lists can be bookmarked and the CSV export uses exactly the same filters. The phone Filters badge counts everything except search; the AM-03 sheet applies only on "Show N results".
- **Reps never see money.** No amount column, no payment block, and the area filter is intersected with their own areas (a foreign area id returns nothing). The visibleTo scope and InspectionPolicy both enforce this.
- **CSV exports stream** with a UTF-8 BOM (so Excel shows ₦) and newest first. They use `lazy()`: `lazyById()` re-sorts by id and would skip rows when ordered by `submitted_at`. Payments export uses `lazyByIdDesc()`.
- **Overview month** is a calendar month: inspections counted by `submitted_at`, revenue by `paid_at` (Africa/Lagos). The picker offers the last 12 months; future or malformed months fall back to the current month.
- **Chart**: a single series drawn with plain HTML/CSS (no chart library), sorted by count. Bar colour is validated for contrast against the surface in both themes; dark mode uses its own step (`--bar: #36a832`) because the dark `--mid` failed the lightness band. Desktop columns have hover tooltips and a Chart/Table toggle (screen readers always get the table); phones show the top 9 with "All N areas".
- **Payments summary** covers the current month; the chips are the design's All / Successful / Failed / Abandoned (pending attempts appear under All).
- **Print / PDF buttons** stay hidden until Phase 6 adds the print route (`printUrl` is null until then).
- **Light theme by default** (user decision, 7 Oct 2026). Dark and Auto are opt-in in the theme switcher; the server renders `data-theme="light"` when no choice is stored, so there is no flash.

## Deployment
- **Pull-based deploys.** The host blocks inbound SSH from the internet but allows outbound connections to GitHub, so GitHub Actions publishes a built `deploy` branch and a cron job on the server pulls it (read-only deploy key) and runs the release steps. DirectAdmin's Git webhook alone can't run Composer or migrations.
- **The `deploy` branch holds built output only**: app code plus `public/build`, without tests, docs, JS/CSS sources or dev config. Its history continues release by release (no force pushes), so every release is a visible commit. `vendor/` is installed on the server from `composer.lock` (`--no-dev`).
- **Release folders + one symlink switch** instead of updating files in place: Composer, migrations and `artisan optimize` run inside the new release, and `app` is switched only when all succeed, so a failed release never replaces the live one and there's no maintenance window. The last 3 releases are kept for `--rollback` (code only; migrations aren't reversed, so they must stay backwards compatible with the previous release).
- **A failed commit isn't retried every minute**: it's recorded and skipped until the next push or `--force`. Rollback holds back the branch head the same way.
- **CI runs on PHP 8.5 and MariaDB 11.4** to match production; the row-locking tests that were MySQL-only locally now run in CI too.

## Phase 6
- **Renamed** to "Building Electrical Inspection & Certification" (client, 7 Oct 2026). The short name in tabs and emails stays KENS. The name lives in `resources/js/lib/brand.ts`.
- **Certificates are issued only after NSD approval** (client decision). Payment still submits the report and issues the ticket at once; the report then sits in the review queue (`review_status`: pending → approved, or → changes_requested → pending again on resubmit). Drafts have no review status.
- **"Request changes" instead of reject.** A refused report would leave a paid fee with nothing to show, so NSD sends it back with a reason (min 10 characters). It reopens for the contractor (edit + attachments), who resubmits without paying again; the ticket number stays. The pay route only accepts drafts.
- **The certificate number is the ticket number**, so the customer has one number to quote, and the existing QR / verify URL works unchanged.
- **Signatory snapshot.** The Head of NSD's name, title and signature image are set by an admin (More → Certificate) and copied onto the inspection at approval — the image is copied into the inspection's own folder — so later changes never alter an issued certificate. Approval is blocked until a signatory is set. Settings are a small cached key/value table (`settings`).
- **Review history** is append-only (`inspection_reviews`: submitted, changes requested + note, resubmitted, approved), shown to admins on the report.
- **Approval takes a second, in-page confirmation** (it emails the contractor and the QR immediately shows "Valid certificate"). Approve / request changes lock the row, so two admins can't both act on the same report.
- **Certificate design** follows the client's sample layout (green frame with cut corners, certificate-number box, statement ribbon, installation details with icons, "Verify this certificate" QR panel, two signatures with a seal, liability clause, tagline) but carries only Kaduna Electric branding. The coat of arms, "Federal Republic of Nigeria", NEMSA's name as issuer, logo and seal, and federal officials' signatures were deliberately left out: Kaduna Electric isn't NEMSA. The seal and badge are our own SVG artwork. The liability clause says the certificate doesn't replace NEMSA certification where the law requires it.
- **Two printouts**: "Certificate" (one A4 page, approved only) and "Full report" (PR-01/PR-02, any submitted inspection, never with payment details). If a report has more than 10 circuits, B4 moves to page 2 (the design's second variant) so page 1 never overflows.
- **Print preview**: one Inertia page per printout without app chrome. Sheets are A4 at 96 dpi, scaled on screen with CSS `zoom`; `@media print` removes the chrome and prints one sheet per page. Printed sheets always use the light tokens (`.paper`) whatever the app theme. Phones get the PR-03 dark preview with swipeable pages; "Share link" uses `navigator.share` (the certificate shares its public verify link — a browser can't share a generated PDF file), and Print / Save as PDF uses the system dialog. `?pdf=1` opens the print dialog once fonts and images have loaded.
- **Public verify** (`/verify/{ticket}`, 30 requests/minute per IP): status (valid certificate / submitted, not yet certified / changes requested / not found with HTTP 404), dates, service area, contractor and the owner masked to "Alhaji M. I.". Nothing else — no address, phone or payment data.
