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
| 0 | Setup | Done |
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
- Icons: `import Search from '~icons/ms/search'` (design ligature, `_` → `-`); never a barrel file.
- Each Inertia page sets its layout in `<script module>`: `export { default as layout } from '@/layouts/AdminLayout.svelte';`
- Colours come from design tokens as Tailwind utilities (`bg-sf`, `text-mut`, `border-line`, `bg-pri text-on-pri`, `bg-ok-bg text-ok` …); theme is `data-theme` on <html>.

## Key models
User (hasOne ContractorProfile; belongsToMany ServiceArea for reps; hasMany Inspection as contractor) · Inspection (belongsTo User contractor, ServiceArea; hasMany InspectionCircuit, InspectionAttachment, Payment) · Payment · FeeSchedule · TicketCounter · ServiceArea.

## Environment notes
MONNIFY_BASE_URL, MONNIFY_API_KEY, MONNIFY_SECRET_KEY, MONNIFY_CONTRACT_CODE, NSD_PHONE, NSD_EMAIL, MAIL_* (transactional SMTP), DEPLOY_SSH_HOST. Production path: /home/buildin1/domains/kens.buildingelectcert.com.ng/app (docroot app/public). Server Composer: php -d memory_limit=-1 ~/bin/composer.

Local dev: PHP 8.4 + MySQL 9 (Homebrew). Databases `kens` (app) and `kens_testing` (row-locking tests), user `kens`. Default test suite runs on SQLite in-memory.

## Workflow
- Plan in tasks/todo.md for anything with 3+ steps; decisions in tasks/decisions.md; lessons from corrections in tasks/lessons.md.
- Never mark a task done without running `php artisan test` and `npm run build`.
- One commit per phase; update the module status table.
- Fix bugs autonomously from logs/tests; ask only for missing secrets or a real product decision.

## Out of scope
NEMSA zonal certification review, observations/defects section, safety and compliance checklist, proposed load demand, self-registration, Inertia SSR, native mobile app.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-svelte/core rules ===

# Inertia + Svelte

- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

</laravel-boost-guidelines>
