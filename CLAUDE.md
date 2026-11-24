# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

IAMS / "AuditFlow" — a retail internal audit management system (stores/branches, audits, findings, action plans, evidence verification, WhatsApp notifications, Excel exports). Stack: Laravel 12 (PHP 8.2), Inertia.js v2 + Vue 3 + Tailwind 3, Spatie Laravel Permission, Ziggy, Breeze scaffolding. UI text, flash messages, WhatsApp templates and many code comments are in Indonesian — keep new user-facing strings in Indonesian to match. `APP_LOCALE=id`. Validation, auth and password messages come from `lang/id/*.php`; add new field names to the `attributes` map in `lang/id/validation.php`. Missing keys fall back to English.

## Commands

```bash
composer setup                 # first-time: install, .env, key, migrate, npm install + build
composer dev                   # serve + queue:listen + pail logs + vite, all concurrently
php artisan serve              # backend only
npm run dev                    # vite only (port 5173; .claude/launch.json "vite")
npm run build                  # production assets

php artisan migrate:fresh --seed   # reset DB with roles, sample data, demo users, CSA stores

composer test                  # config:clear + php artisan test
php artisan test --filter=AuthenticationTest          # single test class
php artisan test tests/Feature/ProfileTest.php        # single file
php artisan test --filter=test_users_can_authenticate # single method

./vendor/bin/pint              # PHP code style (Laravel Pint)
```

Tests run on in-memory SQLite (`phpunit.xml`); local `.env.example` also defaults to SQLite, while README/Docker use MySQL. Existing tests are only Breeze auth/profile scaffolding — there is no domain test coverage yet.

Custom artisan commands (`app/Console/Commands`): `audit:dispatch-scheduled-notifications` (scheduled every minute), `audit:send-reminders` (daily 08:00 Asia/Jakarta), and a WhatsApp test command. Schedules live in `routes/console.php`; locally they only fire if you run `php artisan schedule:work` (or cron `schedule:run`).

Deployment is Docker (`docker compose up -d --build`, see `DEPLOY.md`): services `app` (php-fpm), `web` (nginx), `db` (MySQL 8), `queue` (`queue:work`), `scheduler` (`schedule:work`). `docker/entrypoint.sh` waits for MySQL, runs `storage:link`, `migrate --force`, and caches config/routes/views when `APP_ENV=production`.

## Architecture

### Role-based portals (the core organizing principle)
Roles (Spatie): `admin`, `chief`, `asmen`, `coordinator`, `auditor`, `auditee`. Each portal is a route group in `routes/web.php` with its own URL prefix, route-name prefix, controller namespace and Inertia page directory:

| Prefix / route names | Allowed roles | Controllers | Vue pages |
|---|---|---|---|
| `/admin`, `admin.*` | admin, chief (settings: admin only) | `App\Http\Controllers\Admin` | `resources/js/Pages/Admin` |
| `/coordinator`, `coordinator.*` | coordinator, asmen, chief, admin | `...\Coordinator` | `Pages/Coordinator` |
| `/auditor`, `auditor.*` | auditor | `...\Auditor` | `Pages/Auditor` |
| `/auditee`, `auditee.*` | auditee | `...\Auditee` | `Pages/Auditee` |

Many features (stores, findings, audits, action plans, reports) are implemented **separately per portal** in parallel controllers/pages — a behavior change usually needs to be applied in each portal's controller and page. Exception: the coordinator portal reuses `Auditor\QualityFindingController` for finding-quality routes.

`User::getRedirectRoute()` decides the post-login landing portal. Sidebar navigation for every role is a single data list (`navSections`) in `AppLayout.vue`, shared by the desktop sidebar and mobile drawer — add menu items there. Auditee data access is scoped to the user's stores via the `Audit`/`Finding`/`Evidence` policies called in the Auditee controllers.

`Profile/Edit.vue` renders inside `AppLayout`; the Breeze `AuthenticatedLayout` is unused.

Similarly, `app/Filament` resources and `App\Providers\Filament\AdminPanelProvider` exist (path `admin`), but the provider is **not registered** in `bootstrap/providers.php`, so the Filament panel is inactive; the live admin UI is the Inertia `/admin` portal. Registering it would collide with the Inertia `/admin/*` routes.

### Request/response flow
Controllers return `Inertia::render('Portal/Feature/Page', [...])`; pages are resolved by `resources/js/app.js` from `Pages/**`. Routes are referenced in Vue via Ziggy's `route()` helper, so route names matter. `HandleInertiaRequests` shares `auth.user` (with `roles`), `flash.success|error|message`, and for admin/chief a `notifications` payload of pending user registrations. Controllers signal results via `->with('success'|'error', ...)`, rendered by `Components/FlashMessage.vue`. Layouts: `Layouts/AppLayout.vue` (portal sidebar shell), `GuestLayout.vue` (auth).

Authorization is mostly route-level `role:` middleware (aliases registered in `bootstrap/app.php`). A GET request that fails the role check (for example, the post-login `intended` URL after switching accounts) is caught in `bootstrap/app.php` and redirected to the user's own dashboard with a flash error, instead of a 403. Other HTTP methods still return 403. Non-JSON error responses render `Pages/Error.vue`, which uses `LoginLayout`, through `$exceptions->respond` in `bootstrap/app.php`. 403/404 always do; 500/503 only when `APP_DEBUG` is off. A 419 (expired session) goes back with a flash error. Policies exist for `Audit`, `Finding`, `Evidence` and are used selectively via `$this->authorize(...)`.

### Domain model and workflow
`Store` ←(store_user pivot = auditees)→ `User`; `Audit` belongs to a store, a lead auditor (`auditor_id`) and additional auditors (`audit_auditor` pivot) and a category; `Finding` belongs to an audit, `AuditCategory` (soft-deleted, always loaded `withTrashed()`), and `Sop`; a finding has one `ActionPlan`, one `FindingFollowUp`, one `QualityFinding`, many `Evidence` and `AuditDocument`.

`Finding` holds the workflow constants — use them rather than string literals:
- Status: `OPEN → IN_PROGRESS → WAITING_VERIFICATION → VERIFIED → CLOSED`.
- Severity: `MINOR`, `MEDIUM`, `MAJOR`, `CONTINUOUS` (legacy `CRITICAL`/`OBSERVATION` may exist in old data). `Finding::severityOptions()` is the single source for labels/timelines/badge classes. `CONTINUOUS` findings are excluded from overdue logic (see `scopeOverdue`, `scopeClosedOnTime`, `scopeClosedOverdue`).
- Severity review: coordinators review/adjust severity, which sets `severity_status`, reviewer fields and `is_severity_locked` — once locked the auditor can no longer change it.
- Monetary fields `loss_amount` (NBH) and `surplus_amount` (NBL).

User registration is self-service with admin approval: `approval_status` (`pending|approved|rejected`), `requested_role`, `is_active`. Login supports password and WhatsApp OTP (`OtpLoginController`, `UserOtp`).

### Services (`app/Services`)
- `WhatsAppService` — static methods; `send()` posts to the WagHub gateway (`config('services.waghub.*')` ← `WAG_URL`, `WAG_TOKEN`) and returns bool, never throws. All notification message templates (`notifyFindingCreated`, `notifySeverityReviewed`, `notifyActionPlanReminder`, OTP, account approval, etc.) live here and are called synchronously from controllers/commands. With no `WAG_TOKEN` it just logs a warning and returns false.
- `AuditNotificationService::dispatch()` — sends a queued `AuditNotification` row (generated from admin-configured `NotificationRule`s, e.g. H-7/H-3/H-1) and updates its status (`SENT`/`FAILED`/`INACTIVE`).
- `ReportExportService` — streamed `.xls` exports (`exportFindings`, `exportStores`, `exportSummary`), shared by the admin/coordinator/auditor report controllers.

### File storage
Uploads (SOP documents, evidence photos, audit documents) use `config('filesystems.default')` as the disk (S3-compatible supported via `league/flysystem-aws-s3-v3` and `AWS_*` env), with fallback cleanup on the `public` disk for older files. Follow this pattern rather than hardcoding a disk. `FILESYSTEM_DISK` must be `public` or `s3`. With Laravel 12's `local` disk, files land in `storage/app/private`, and every `/storage/...` link returns 403. The Docker volume and nginx also serve only `storage/app/public`. Locally, `APP_URL` must include the port (`http://localhost:8000`), or file links point to port 80.

### UI conventions
Keep the existing color palette (navy sidebar `#111827`, blue-600 accent, red/amber/blue/emerald status tints) — the owner asked that colors not be changed. Styling follows `AppLayout.vue` and `Pages/Admin/Dashboard.vue`: minimum 12px text (`text-sm` for content), sentence case labels without trailing periods, no uppercase-tracked labels, `font-medium` instead of bold, 4px spacing grid, `rounded-xl` cards / `rounded-lg` controls / `rounded-md` badges, 1.5 stroke icons, `transition-colors duration-150`, `tabular-nums` on figures. Admin/Coordinator page pairs should stay visually identical.

The owner wants little text ("jangan kebanyakan text"), so pages follow these conventions:
- No subtitle under the page h1. A record count goes next to the title as a muted number.
- Filters sit inside the table card, and search uses the placeholder "Cari".
- Stats are one combined panel (see `Pages/Auditor/Dashboard.vue`), not separate cards.
- A row with a single "open" action is clickable and shows a chevron. Secondary actions are icon buttons with `title`/`aria-label`.
- Card headers are `h-14` bars with no description.
- The top-bar title is hidden on desktop because each page has its own h1.
- Paginated tables use client-side `<Pagination>` with 10 rows per page.
  - The desktop table wrapper gets `v-stable-table="<list>.length"` (directive in `resources/js/directives/stableTable.js`). It locks column widths and stops the table from shrinking between pages.
  - The `<tbody>` gets `:key="currentPage" class="page-fade"` for a short fade.
  - The Reports "Rekap per cabang" table uses a fixed `<colgroup>` and a spacer row instead.

Reference pages: `Pages/Auditor/Dashboard.vue`, `Pages/Auditor/Audits/Index.vue`.

Interior pages share the look of the login page (`Pages/Auth/Login.vue`, `Layouts/LoginLayout.vue`):
- **Cards:** `rounded-xl border border-gray-200 bg-white shadow-sm`.
- **Inputs, selects and textareas:** `rounded-lg border-gray-300 bg-white shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20`. Contextual focus colors (red for reset, emerald for approve, rose for reject) stay.
- **Labels:** `text-sm font-medium text-gray-900`, with `<span class="text-red-600">*</span>` on required fields.
- **Headings:** page h1 and card h2 are `font-semibold`.
- **Buttons:** primary is `bg-blue-600 hover:bg-blue-700 shadow-sm`; secondary is white with a `border-gray-300` border and `shadow-sm`.
- **Sidebar:** stays navy.

WhatsApp OTP login lives on its own page (`/login/whatsapp`, `Auth/LoginWhatsapp.vue`). It is rendered by `AuthenticatedSessionController@createWhatsapp`, and `OtpLoginController` returns there via `back()`.

### Admin maintenance
`Admin\SystemSettingController` (admin only) can toggle demo accounts, reset transactional data, and factory-reset — destructive operations that also delete stored files.

## Demo accounts
Seeded by `migrate:fresh --seed`, password `password`: `admin@`, `chief@`, `asmen@`, `kordinator@`, `auditor@`, `auditee@auditflow.com`.
