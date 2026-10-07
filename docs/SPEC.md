# Kaduna Electric Visual Site Inspection (KENS): Claude Code kickoff

## Project overview

Build a web app for Kaduna Electric's **New Service Department (NSD)** that replaces the paper **Visual Site Inspection Report**. Licensed electrical contractors use it as follows:

1. Fill the report on site, usually on a phone.
2. Pay an inspection fee through **Monnify**.
3. Receive a system-generated **ticket number**.

NSD admins see every submission and manage accounts and the fee. Service reps see and print only the submissions in their 1 to 3 assigned service areas.

- **Production URL:** `https://kens.buildingelectcert.com.ng`
- **Hosting:** DirectAdmin shared hosting. Read "Hosting constraints" before making any infrastructure choice.
- **Designs:** the finished UI designs are in GitHub repo `AbdulsalamAbdulrahman/NS-Visual-Inspection-App`, branch `design`. **They are the source of truth for layout, copy, states and visual style.**

---

## Tech stack

- **Backend:** Laravel 13 on PHP 8.5. Production runs PHP 8.5.10, so stay compatible with 8.3–8.5.
- **Frontend:** Inertia.js v3 + Svelte 5 (runes), Tailwind CSS v4, TypeScript.
- **Auth:** Laravel Fortify, from the official Svelte starter kit. **Turn registration off.**
- **Database:** MariaDB 11.4 in production. Use the `mysql` driver and develop locally against MariaDB or MySQL 8. **PostgreSQL is not available on the host**, so use no Postgres-only features.
- **Tests:** Pest. Use SQLite in-memory for speed, but run tests that depend on row locking against MariaDB/MySQL.
- **Roles:** a `role` enum column plus Policies. Don't add Spatie Permission; there are only three fixed roles.
- **PHP packages, only these unless clearly needed:**
  - `chillerlan/php-qrcode` for SVG QR codes
- **npm packages, only these unless clearly needed:**
  - `signature_pad`
  - `browser-image-compression`
  - `exifr`, to read GPS from photo EXIF before compressing
  - `idb`, for IndexedDB
  - `unplugin-icons` + `@iconify-json/material-symbols`, for tree-shaken Material Symbols Rounded icons as SVG instead of the multi-MB icon font
  - `@fontsource/public-sans` + `@fontsource/ibm-plex-mono`, to self-host fonts with no Google Fonts call

---

## Hosting constraints (non-negotiable)

The production server is shared hosting. It has been checked and has:

- PHP 8.5 with OPcache, `pdo_mysql`, `gd`, `imagick`, `exif`, `intl`, `bcmath`, `zip`, `sodium` and `fileinfo`. `proc_open` is allowed.
- `memory_limit` 128M, upload/post max 64M, no execution time limit.
- MariaDB 11.4, git 2.43, and Composer at `~/bin/composer`.
- App path: `/home/buildin1/domains/kens.buildingelectcert.com.ng/app`.
- Document root already set to `.../app/public`.

These shape the build:

1. **No Node.js on the server.**
   - Assets are built locally with `npm run build` and uploaded as `public/build`.
   - **Do not enable Inertia SSR** and do not add anything that needs a Node process in production.
2. **No Redis and no Supervisor.**
   - Use `QUEUE_CONNECTION=database`, `CACHE_STORE=file` and `SESSION_DRIVER=database`.
   - The queue is drained by the scheduler (a single cron runs `schedule:run` every minute):
     ```php
     Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
     ```
   - Only email and housekeeping go on the queue. **Payment confirmation and ticket generation must run synchronously.**
3. **No headless Chrome.**
   - Printing uses an HTML page with `@media print` CSS, saved through the browser's own Print / Save as PDF.
   - Don't use Browsershot or Puppeteer. Don't use dompdf unless asked later.
4. **Private files.** Store uploads on the `local` (private) disk under `storage/app/private/inspections/{uuid}/`. Serve them through an authorised controller route that checks role and service area. Don't rely on the `storage:link` symlink.
5. **Composer on the server** runs as `php -d memory_limit=-1 ~/bin/composer install --no-dev -o`, because the default 128M limit is too low.
6. **HTTPS is required.** Browser geolocation and service workers only work over HTTPS.
7. **Keep requests light.**
   - Paginate every list, eager-load relationships, and add the indexes listed in the data model.
   - Run `php artisan optimize` on every deploy.
   - Lazy-load Inertia pages with `import.meta.glob`, so contractors never download admin code.
8. **Timezone:** set `app.timezone` to `Africa/Lagos`. The server clock is in a different timezone.

---

## Designs: how to use them

```bash
git clone -b design --depth 1 https://github.com/AbdulsalamAbdulrahman/NS-Visual-Inspection-App /tmp/kens-design
mkdir -p docs/design && cp -r /tmp/kens-design/* docs/design/ && rm -rf /tmp/kens-design
```

The `.dc.html` files are design canvases.

- **Reading them:** read the HTML source for layout, copy and inline styles. The `<script data-dc-script>` block holds the sample data, and `{{ }}` are template placeholders.
- **Tokens:** the `:root` and `[data-theme=dark]` CSS variables in `01 Foundations and Sign-in.dc.html` are the design tokens. Port them into the Tailwind v4 `@theme` and CSS variables, so light, dark and auto themes all work.
- **Replacing the starter kit look:** rebuild the starter kit's default UI to match the designs. Keep its auth plumbing and drop its look.

| File | Screens |
|---|---|
| `01 Foundations and Sign-in` | Tokens, type, components, sign-in, first-login password, forgot password, suspended account (mobile + desktop) |
| `02 Contractor Mobile - Form` | The 9-step form (CF-01 to CF-12): validation, GPS states, circuits, offline banner, attachments, attestation |
| `03 Contractor Mobile - Review Pay Ticket` | Home, empty state, profile, review, payment states, ticket, submission detail |
| `04 Contractor Desktop` | Desktop home, step rail with circuits table, review with payment panel, ticket |
| `05 Admin Desktop` + `Admin Sidebar` | Overview, inspections list and detail, lightbox, contractors, service reps, service areas, payments, fee settings |
| `06 Admin Mobile` | Bottom tabs, card lists, filter sheet, action sheets, delete confirmation, add contractor, More tab |
| `07 Service Rep` | Desktop, tablet and mobile lists; empty state; detail (no payment); profile menu |
| `08 Print Report` + `Inspection Report Page` | A4 two-page print layout; phone print preview |

**Terminology: the regulator is NEMSA.** Use "NEMSA category" and "NEMSA registration no." (format like `NEMSA/A/2023/0142`). Never use "EBEI".

---

## Roles and rules

| Role | Can do |
|---|---|
| `contractor` | Create inspections, save and resume drafts, review, pay, view and print their **own** submissions, view profile, change password. **Cannot edit after submission.** |
| `admin` | Everything: all inspections in all areas, contractors, service reps, service areas, payments, fee, CSV export. |
| `rep` | Read-only access to inspections whose `service_area_id` is in their assigned areas (1 to 3). View and print only. **Reps never see payment data**, so hide the amount column and the payment section. |

**Accounts**
- No self-registration. Admin creates contractors and reps.
- On creation, the system generates a temporary password and emails the login details. The account status is **Invited**.
- On first sign-in the user must set a new password (`must_change_password` middleware), which moves them to **Active**.
- Admin can:
  - suspend or reactivate an account
  - resend login details (new temporary password, emailed)
  - send a password reset link
  - delete an account

**Deleting a user**
- Soft-delete the user; their submitted inspections stay on record.
- The UI needs an in-page confirmation step. Browser `confirm()` is not allowed.
- Offer "Suspend instead" in the confirmation.

**Suspended users** cannot sign in. Show the "This account is suspended" state from the AU-05 design.

**Forgot password** sends a reset link. The design also shows "Call NSD / Email NSD", so take those from config values `NSD_PHONE` and `NSD_EMAIL`, with placeholders in `.env`.

**First admin:** add an artisan command `app:create-admin {email} {name}` that creates an admin and prints a temporary password.

---

## The inspection form (contractor)

The form has nine steps with a progress indicator ("1 / 9"). **Save draft** and an auto-save indicator ("Saved 10:42" / "Saving…" / "On device") appear on every step.

Drafts can be incomplete. **Full validation runs only on Review and at payment.** Out-of-range readings show soft warnings and never block.

**Step 1 · Section A: Basic customer information**
- **Form 74 number** (required, text)
- **Property owner name** (required)
- **Property address** (required, multi-line)
- **Service area** (required, dropdown of active areas). Help text: "Decides which area office can view this report."
- **Purpose of property:** residential, commercial, industrial, government
- **Connection type:** single phase, three phase
- **Voltage level:** 240 V, 415 V, 11 kV, 33 kV
- **GPS location** (required)
  - Uses the browser Geolocation API with high accuracy. Store latitude, longitude, accuracy in metres and capture time.
  - Design these states: capturing, captured, permission denied, low accuracy (worse than ±20 m), with a "Use anyway" option.
- **Inspection date** (automatic, read-only)

**Step 2 · B1: Earthing system**
- **Earth electrode size** in ft. Hint "Minimum 6 ft"; warn if below.
- **Earth conductor size** in mm². Hint "Minimum 10 mm²"; warn if below.
- **Earth resistance** in Ω. Warn if above 2 Ω, and highlight the value on the report.
- **Earth inspection pit available?** yes / no

**Step 3 · B2: Primary supply over-current protection**
- **Circuit breaker:** rated current (A), and Standard? yes / no
- **Cut-out fuse:** rated current (A), and Standard? yes / no
- **Number of poles**

**Step 4 · B3: Mains checklist**
- **Distribution board:** seen / not seen. If seen: standard / not standard.
- **Change-over switch:** seen / not seen. If seen: standard / not standard.
- **Main switch type (BS):** standard / not standard
- **Circuit breaker type (BS EN 60898-1, Type B, over-current):** standard / not standard
- **Secondary main protection:**
  - rated current (A)
  - MCB / RCD
  - standard / not standard
- **Socket outlets type (BS 1363-2):** standard / not standard

**Step 5 · B4: Description of wiring**

A repeatable list of circuits, labelled C1, C2 and so on.

- **Circuit description:**
  - lighting, socket outlet, ring mains, cooker, immersion heater, air conditioner, electrical motor, borehole pump, outdoor equipment
  - or "other", which reveals a text field
- **Rating** (A)
- **Conductor size** (mm²)
- **Condition** (required, exactly one):
  - satisfactory
  - improvement required
  - urgent attention required
  - does not comply with standard
- **Observation** (optional)

Display: on mobile, show cards plus a bottom sheet for add/edit (CF-08 / CF-09). On desktop and in print, show a table. Condition colours run green → amber → orange → red. Every condition also has an icon and its label, so colour is never the only signal.

**Step 6 · Section C: System details**
- **Earthing system type:** TT, TN-S, TN-C-S, IT
- **Number of distribution boards**
- **Number of sub-circuits**
- **Main cable size** (mm²)
- **Conductor type:** copper / aluminium
- **Wiring method:** surface, conduit, trunking, or other (with text)
- **Cable insulation type** (text, e.g. PVC, XLPE)

**Step 7 · Attachments**
- **Electrical layout and load schedule:** required; PDF or image.
- **Photos of DB and earthing:** required, at least one, multiple allowed.
  - Hint: use a GPS-stamp camera.
  - Offer camera and gallery inputs.
  - Before compressing a photo, read its EXIF GPS and capture time with `exifr` and store them.
  - Then compress in the browser to about 1600 px on the long edge and 300–500 KB.
- **Test equipment calibration certificate:** optional.
- Show upload progress, thumbnails and a remove button.
- Server-side, validate MIME type and size (max 10 MB per file after compression).

**Step 8 · Section D: Attestation**
- **Read-only inspector details**, pulled from the contractor profile: name, NEMSA category, NEMSA registration no., COREN no. (if any) and firm name (if corporate).
- **When the inspection is submitted, copy these details onto the inspection record,** so later profile changes don't alter old reports.
- **Declaration:** a checkbox with this text:
  > I hereby certify that the electrical installation at the location above was inspected and tested in accordance with the Nigerian Electricity Supply Installation Standards (NESIS), the Nigerian Electricity Health and Safety Code, and all applicable regulations. The findings are accurate and complete to the best of my knowledge.
- **Signature:** drawn with `signature_pad` and stored as a PNG, with a Clear button.
- **Date:** automatic.

**Step 9 · Review**
- A read-only summary of every section, each with an Edit link.
- A banner at the top lists missing items, each with a Fix link.
- The pay button stays disabled ("Fix 2 items to pay") until everything is valid.

---

## Payment and ticket (Monnify)

Payment is the last step. **A submission only goes through after Monnify confirms payment. After that, the inspection is locked.**

**Monnify details.** Confirm every endpoint and SDK detail against the current docs at https://developers.monnify.com before coding; don't rely on memory.

- **Auth:** call the login endpoint with Basic `base64(apiKey:secretKey)` to get a bearer token. Cache the token until shortly before it expires.
- **Base URLs:** sandbox `https://sandbox.monnify.com`, live `https://api.monnify.com`.
- **`.env` keys:** `MONNIFY_BASE_URL`, `MONNIFY_API_KEY`, `MONNIFY_SECRET_KEY`, `MONNIFY_CONTRACT_CODE`.

**Flow**
1. **The server decides the amount.**
   - On "Pay", re-validate the whole inspection.
   - Take the fee currently in effect.
   - Create a `payments` row with status `pending`, a unique `payment_reference` of our own, and that amount.
   - Initialise the Monnify transaction on the server.
   - Open Monnify checkout: either the Web SDK modal or a redirect to `checkoutUrl`. Choose whichever works more reliably on mobile, and document why.
2. **On completion or return**, call our `verify` endpoint. **Never trust client callbacks alone.** It queries Monnify's verify-transaction API, then:
   - Mark the payment as paid only when `paymentStatus` is `PAID` or `OVERPAID` **and** `amountPaid` ≥ the expected amount.
   - Treat `PENDING` as still waiting. The UI polls for up to about 2 minutes, matching the CP-02 processing state.
   - Treat `FAILED`, `EXPIRED`, `REVERSED` and `PARTIALLY_PAID` as failed. Leave the inspection as a draft and offer retry.
3. **Webhook as backup:** `POST /webhooks/monnify`.
   - Exclude it from CSRF in `bootstrap/app.php`.
   - In production, check the `monnify-signature` header: HMAC-SHA512 of the raw request body using the client secret key. Monnify only sends the signature in production, not sandbox.
   - Even when the signature is valid, **re-verify the transaction through the API** before acting.
   - Optionally restrict the route to Monnify's documented IP, set in config.
4. **Duplicate-safe finalisation.** Both the verify endpoint and the webhook call a single `FinalizePaidInspection` action. Inside one DB transaction it:
   - locks the payment row with `lockForUpdate()`
   - returns early if the payment is already finalised
   - marks the payment paid, storing channel, amount paid, Monnify `transactionReference` and paid time
   - **generates the ticket**
   - sets the inspection to `submitted` with `submitted_at`, and snapshots the attestation details
   - dispatches the confirmation email, which is queued
5. **Ticket format:** `KE-NSD-{YYYY}-{000123}`.
   - Use a `ticket_counters` table, one row per year.
   - Increment with `lockForUpdate()` inside the same transaction.
   - Put a unique index on `inspections.ticket_no`.
   - Write a test showing that concurrent finalisation never produces duplicates.
6. **Success screen (CT-01)** shows:
   - the ticket
   - a QR code
   - owner, address, area, amount, payment reference and date
   - actions: View report, Print report, Back to my inspections

**Fee settings (admin)**
- A `fee_schedules` table: amount in **kobo**, `effective_from` date, optional reason, `created_by`.
- The current fee is the latest schedule whose `effective_from` is on or before today. A future-dated row shows as "Scheduled", and the admin can cancel it.
- A payment stores the amount at the moment it was initialised.
- Seed ₦15,000 effective from the launch date.
- Store all money as integer kobo and display it as `₦15,000.00`.

**Payments page (admin)**
- Lists Monnify reference, ticket, contractor, amount, channel, status (Successful / Failed / Abandoned) and date.
- Has filters and CSV export.
- Abandoned means a pending payment older than 24 hours. A scheduled command marks these.

---

## Viewing, printing and verifying

- **Inspection lists**
  - Search by ticket, owner name or Form 74 number.
  - Filters: service area, purpose, connection type, date range.
  - Paginate 25 per page.
  - Admin can export CSV, streamed (`response()->streamDownload`).
  - **Reps:** the list is scoped to their areas with a query scope that is also enforced in Policies. Show their area chips plus "All my areas". Use the SR-M2 empty state.
- **Inspection detail**
  - Show the full report.
  - Show GPS with an "Open in Google Maps" link (`https://www.google.com/maps?q=lat,lng`).
  - Attachments open in a lightbox. On mobile they swipe and include the photo's EXIF GPS.
  - Admin also sees the payment block.
- **Admin overview**
  - KPIs: inspections this month, revenue this month, active contractors (out of total), drafts in progress.
  - Bar chart of submissions by service area, drawn in Svelte SVG. Don't add a chart library.
  - Recent submissions.
- **Print (`/inspections/{uuid}/print`)**
  - Follow the A4 layout in `08 Print Report` / `Inspection Report Page`. Page 1 has the header, A and B1–B4. Page 2 has C, GPS, attachments list and D with the signature image.
  - The header has the logo, title, ticket, submitted date and QR code.
  - **Payment details are left off the print.**
  - The footer shows the generated time, the ticket and "Page X of 2".
  - The page is the same for every role, but access is still checked against role and area.
  - On phones, show the PR-03 full-screen preview with Print and Share as PDF actions. Use `window.print()`, and `navigator.share` where it's available.
- **QR / public verification**
  - The QR code encodes `https://kens.buildingelectcert.com.ng/verify/{ticket_no}`.
  - That public, rate-limited page shows only:
    - the ticket
    - that the report is valid and submitted
    - the submitted date
    - the service area
    - the contractor name
    - the owner name masked (e.g. "Alhaji M. I.")
  - **No other personal data.**

---

## Offline drafts (PWA)

Contractors work on site with weak or no network.

- **App shell:** a service worker (hand-written or `vite-plugin-pwa`) caches the built shell and the contractor form pages.
- **Saving:** form state saves to **IndexedDB** on every change.
- **Syncing:** when online, a debounced auto-save (about 2 s) PATCHes the draft to the server. When offline, show the CF-10 banner ("No network. Draft is saved on this phone and will sync when you're back online.") and the "On device" chip. Pending attachments queue in IndexedDB and upload when the connection returns.
- **Creating drafts offline:** use client-generated UUIDs for draft inspections.
- **Payment always needs network.** Pay is disabled while offline.

---

## Data model (MariaDB)

All tables use timestamps. Inspections are routed by `uuid`, never by numeric ID. The columns for B and C are typed and nullable while the inspection is a draft.

- **`users`**
  - `id`, `uuid`, `name`, `email` (unique), `phone`
  - `role` enum: admin, contractor, rep
  - `status` enum: invited, active, suspended
  - `must_change_password` bool, `last_active_at`, `password`
  - soft deletes
- **`contractor_profiles`**
  - `user_id` (unique FK)
  - `nemsa_category` enum: cat_a, cat_b, cat_c, corporate
  - `nemsa_reg_no` (unique), `coren_no` (nullable)
  - `firm_name`: nullable, but required when category is corporate
- **`service_areas`:** `id`, `name` (unique), `is_active`
- **`rep_service_area`:** pivot of `user_id` and `service_area_id`, unique together. Enforce 1–3 areas per rep in validation.
- **`inspections`**
  - **Identity and status:**
    - `id`, `uuid`, `contractor_id`, `service_area_id` (nullable while a draft)
    - `status` enum: draft, submitted
    - `current_step`, `ticket_no` (unique, nullable)
  - **Section A:** `form74_no`, `owner_name`, `property_address`, `purpose`, `connection_type`, `voltage_level`, `gps_lat` and `gps_lng` (decimal 10,7), `gps_accuracy_m`, `gps_captured_at`, `inspection_date`
  - **B1:** `earth_electrode_ft`, `earth_conductor_mm2`, `earth_resistance_ohm`, `earth_pit`
  - **B2:** `cb_rated_a`, `cb_standard`, `fuse_rated_a`, `fuse_standard`, `poles`
  - **B3:** `db_seen`, `db_standard`, `changeover_seen`, `changeover_standard`, `main_switch_standard`, `cb_type_standard`, `secondary_rated_a`, `secondary_type` (mcb/rcd), `secondary_standard`, `socket_outlets_standard`
  - **C:** `earthing_system_type`, `db_count`, `sub_circuit_count`, `main_cable_mm2`, `conductor_type`, `wiring_method`, `wiring_method_other`, `cable_insulation`
  - **D:** `declaration_accepted_at`, `signature_path`, and the snapshot fields `inspector_name`, `inspector_nemsa_category`, `inspector_nemsa_reg_no`, `inspector_coren_no`, `inspector_firm_name`
  - `submitted_at`
  - **Indexes:** (`service_area_id`, `submitted_at`), (`contractor_id`, `status`), `status`, `form74_no`, `owner_name`
- **`inspection_circuits`**
  - `inspection_id`, `position`, `description`, `description_other`
  - `rating_a`, `conductor_mm2`
  - `condition` enum: satisfactory, improvement_required, urgent_attention_required, non_compliant
  - `observation`
- **`inspection_attachments`**
  - `inspection_id`, `type` enum: layout, photo, calibration
  - `path`, `original_name`, `mime`, `size_bytes`
  - `exif_lat`, `exif_lng`, `taken_at` (all nullable)
- **`payments`**
  - `inspection_id`, `contractor_id`
  - `payment_reference` (unique, ours), `transaction_reference` (unique, Monnify's)
  - `amount_kobo`, `amount_paid_kobo`, `channel`
  - `status` enum: pending, paid, failed, abandoned
  - `paid_at`, `gateway_payload` (json)
  - index (`status`, `created_at`)
- **`fee_schedules`:** `amount_kobo`, `effective_from`, `reason`, `created_by`, soft deletes (used for cancelling a scheduled change)
- **`ticket_counters`:** `year` (PK), `last_number`

**Seeders**
- Admin `Hadiza Musa`.
- Contractors from the designs: Engr. Yusuf Bello (Cat A, NEMSA/A/2023/0142, COREN R.12873, Bello Power Systems Ltd), Aisha Lawal, Chinedu Okafor, Danladi Electrical Services (Corporate), and Emmanuel Gajere (suspended).
- Reps: Grace Ayuba (Barnawa, Kakuri, Sabon Tasha) and Abubakar Shehu (Kawo, Rigasa).
- Service areas (**placeholders until the client sends the real list**; admins can edit them in the UI): Barnawa, Kawo, Doka, Rigasa, Tudun Wada, Sabon Tasha, Kakuri, Zaria, Kafanchan, Saminaka, Sokoto, Gusau, Birnin Kebbi.
- Sample inspections with Kaduna addresses.
- Keep demo data in a separate `DemoSeeder` that never runs in production.

---

## Skills and tooling to install first

Run in the project root after scaffolding:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

- `boost:install` sets up the Boost MCP server, generates the stack guidelines, and auto-installs the Inertia-Svelte, Pest and Tailwind skills it detects.
- Then install the official Svelte skills **`svelte-code-writer`** and **`svelte-core-bestpractices`**. Get them from the Claude Code plugin marketplace, or download them from the `sveltejs/ai-tools` releases into `.claude/skills/` (see https://svelte.dev/docs/ai/skills). Use `svelte-code-writer`'s autofixer on every `.svelte` file before finishing it.

Don't add other third-party skills. `CLAUDE.md` captures the conventions.

---

## How to work

- **Build in phases.** Work through them in order, mostly on your own:
  1. Write the phase's tasks to `tasks/todo.md`.
  2. Build the phase.
  3. Write Pest feature tests for its rules.
  4. Run `php artisan test` and `npm run build`. Both must pass.
  5. Update the module status in `CLAUDE.md`.
  6. Make one commit per phase with a clear message.
- **When to stop and ask:** only for missing secrets (use Monnify **sandbox** placeholders until real keys exist) or a real product decision not covered here. Otherwise make the sensible call and note it in `tasks/decisions.md`.
- **Corrections:** after any correction, record what to avoid in `tasks/lessons.md`.
- **Conventions:**
  - Thin controllers and Form Requests.
  - Single-purpose Action classes for business operations: `CreateContractor`, `SaveDraft`, `InitPayment`, `FinalizePaidInspection`, `GenerateTicket`.
  - Policies for every inspection, attachment and print access.
  - Backed PHP enums for every enum column.
  - Use `declare(strict_types=1)`.
  - Model config: use class properties (`$fillable`, `$casts`) consistently.
  - Money in kobo. Inertia props shaped by dedicated Resource or DTO classes, so no full models go to the client.
- **UI:**
  - Match the designs.
  - Contractor controls are at least 56 px tall, with primary actions in the bottom thumb zone.
  - Light, dark and auto themes.
  - Tabular figures and IBM Plex Mono for tickets, coordinates, readings and money.
  - Visible focus states and `prefers-reduced-motion` support.
- **Must-have tests:**
  - A rep cannot see another area's inspection, attachment or print page.
  - A contractor cannot see another contractor's inspection.
  - A submitted inspection cannot be edited.
  - Payment finalisation is duplicate-safe. Use `Http::fake` for Monnify.
  - Ticket numbers are sequential and unique.
  - Fee changes take effect from their date.
  - Suspended users cannot sign in.
  - First sign-in forces a password change.

## Phases

| # | Phase | Delivers |
|---|---|---|
| 0 | Setup | Scaffold from the official Svelte starter kit (Laravel's built-in auth, Pest), switch to MariaDB, Boost and skills, copy designs into `docs/design/`, `CLAUDE.md`, `docs/SPEC.md`, tokens and fonts, themed app shells (contractor mobile and desktop, admin sidebar and bottom tabs, rep header) |
| 1 | Auth and accounts | Fortify with registration off, role and status enums, sign-in, first-login password, forgot password, suspended state, role middleware, `app:create-admin` |
| 2 | Admin management | Service areas, contractors (create drawer/sheet, row actions, delete confirmation, emails), service reps (1–3 areas), fee settings with history and scheduling |
| 3 | Inspection form | Drafts, all 9 steps, circuits, GPS states, attachments (compression and EXIF), signature, auto-save, validation, review page, contractor home and profile |
| 4 | Payment and ticket | Monnify init/checkout/verify/webhook, duplicate-safe finalisation, ticket counter, success screen, failed and processing states, confirmation email |
| 5 | Viewing | Contractor submissions; admin overview, inspections list/filters/export/detail/lightbox, payments page; rep scoped list and detail |
| 6 | Print and verify | A4 print page, QR code, phone print preview, public `/verify/{ticket}` page |
| 7 | Offline (PWA) | Service worker, IndexedDB drafts and attachment queue, sync, offline banner |
| 8 | Production readiness | Indexes reviewed, N+1 check, abandoned-payment command, scheduler entries, `.env.example`, `deploy.sh`, `docs/DEPLOY.md` |

**`deploy.sh` (run locally) must:**
1. Run `npm ci && npm run build`.
2. `rsync` `public/build/` to the server.
3. Over SSH, run:
   ```bash
   cd ~/domains/kens.buildingelectcert.com.ng/app
   git pull
   php -d memory_limit=-1 ~/bin/composer install --no-dev -o
   php artisan migrate --force
   php artisan optimize
   php artisan queue:restart
   ```

Keep the SSH host in an env variable. **Commit `public/build` to git: no.** Keep it in `.gitignore` and upload it with rsync.

`docs/DEPLOY.md` must cover:
- the first-time server setup: `.env`, `key:generate`, `app:create-admin`
- the cron line, using the PHP CLI path from `which php`: `* * * * * php /home/buildin1/domains/kens.buildingelectcert.com.ng/app/artisan schedule:run >> /dev/null 2>&1`
- SMTP settings for a transactional mail provider
- the Monnify webhook URL to register

---

## First task

1. Scaffold the app with `laravel new`. Choose the **Svelte** starter kit, Laravel's built-in authentication (not WorkOS) and Pest.
2. Save this entire prompt as `docs/SPEC.md`.
3. Copy the designs into `docs/design/` (commands above).
4. Install Boost and the Svelte skills.
5. Create `CLAUDE.md` in the project root with the content below. If Boost generated one, merge this into it, keeping Boost's own guideline section.
6. Do Phase 0, then carry on through the phases.

```markdown
# KENS: Kaduna Electric Visual Site Inspection

## Project overview
Web app for Kaduna Electric's New Service Department. Contractors fill the Visual Site Inspection Report on site (mostly on phones), pay a fee via Monnify, and get a ticket (KE-NSD-YYYY-000123). Admins see and manage everything; service reps view and print submissions in their 1–3 assigned service areas only. Full spec: docs/SPEC.md. UI designs: docs/design/ (source of truth for layout, copy and states).

## Tech stack
Laravel 13 (PHP 8.5), Inertia v3 + Svelte 5 (runes) + TypeScript, Tailwind v4, Fortify (registration off), MariaDB 11.4 (mysql driver), Pest, chillerlan/php-qrcode, signature_pad, browser-image-compression, exifr, idb, unplugin-icons (Material Symbols Rounded), self-hosted Public Sans + IBM Plex Mono via @fontsource.

## Architecture notes
- Shared hosting (DirectAdmin): no Node, Redis or Supervisor in production. No Inertia SSR. Assets built locally and rsynced.
- Queue = database, drained by the scheduler (`queue:work --stop-when-empty`) from one cron. Only email and housekeeping are queued; payment finalisation and tickets are synchronous.
- Cache = file, sessions = database. Timezone Africa/Lagos.
- Uploads on the private local disk, served through authorised controller routes. No storage:link.
- Printing = HTML + print CSS, browser's Save as PDF. No headless Chrome.
- Roles: users.role enum (admin, contractor, rep) + Policies. Rep scoping via a query scope AND Policies.
- Money in integer kobo. Inspections routed by uuid.
- The regulator is NEMSA ("NEMSA category", "NEMSA registration no."). Never "EBEI".

## Module status
| # | Module | Status |
|---|--------|--------|
| 0 | Setup | Not started |
| 1 | Auth and accounts | Not started |
| 2 | Admin management | Not started |
| 3 | Inspection form | Not started |
| 4 | Payment and ticket | Not started |
| 5 | Viewing | Not started |
| 6 | Print and verify | Not started |
| 7 | Offline (PWA) | Not started |
| 8 | Production readiness | Not started |

## Conventions
- declare(strict_types=1); backed enums for every enum column; model config via class properties ($fillable, $casts).
- Thin controllers + Form Requests; business logic in single-purpose Action classes (app/Actions).
- Inertia props via dedicated Resource/DTO classes; never send full models.
- Pest feature tests for every access rule and money/ticket path; Http::fake for Monnify.
- Svelte: runes only, $derived over $effect, keyed each blocks, snippets over slots; run the svelte-code-writer autofixer before finishing a component.
- UI: match docs/design; contractor touch targets ≥ 56 px; light/dark/auto themes; IBM Plex Mono + tabular figures for tickets, coordinates, readings and money; status is never shown by colour alone.
- No browser alert()/confirm(): confirmations are in-page.

## Key models
User (hasOne ContractorProfile; belongsToMany ServiceArea for reps; hasMany Inspection as contractor) · Inspection (belongsTo User contractor, ServiceArea; hasMany InspectionCircuit, InspectionAttachment, Payment) · Payment · FeeSchedule · TicketCounter · ServiceArea.

## Environment notes
MONNIFY_BASE_URL, MONNIFY_API_KEY, MONNIFY_SECRET_KEY, MONNIFY_CONTRACT_CODE, NSD_PHONE, NSD_EMAIL, MAIL_* (transactional SMTP), DEPLOY_SSH_HOST. Production path: /home/buildin1/domains/kens.buildingelectcert.com.ng/app (docroot app/public). Server Composer: php -d memory_limit=-1 ~/bin/composer.

## Workflow
- Plan in tasks/todo.md for anything with 3+ steps; decisions in tasks/decisions.md; lessons from corrections in tasks/lessons.md.
- Never mark a task done without running `php artisan test` and `npm run build`.
- One commit per phase; update the module status table.
- Fix bugs autonomously from logs/tests; ask only for missing secrets or a real product decision.

## Out of scope
NEMSA zonal certification review, observations/defects section, safety and compliance checklist, proposed load demand, self-registration, Inertia SSR, native mobile app.
```
