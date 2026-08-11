# Hotezo

Hotezo is two products in one platform:

- **A public booking platform** — a global hub listing 1,000+ hotels, each with its own
  responsive, SEO-friendly landing page and a full online booking flow.
- **A back-office management system** — every booking (online or via an OTA like Booking.com,
  MakeMyTrip, Agoda, Airbnb, Expedia, Goibibo) automatically gets OTA commission, Hotezo's
  commission, GST, TDS, and TCS calculated, with settlements, GST-compliant invoices, email
  notifications, and analytics — surfaced instantly across three dashboards per hotel:
  Super Admin, Hotel Admin, and Hotel Manager / Front Desk.

This repository currently contains the **project skeleton** (folder structure, the custom
lightweight MVC core, the design system, a styled placeholder landing page), the **complete
MySQL/MariaDB schema** (27 tables, migrations, and seed data), the **auth + authorization system**
(login, sessions, role levels, hotel scoping, per-user permission overrides), the **authenticated
app shell** (sidebar, topbar, mobile nav, confirm dialogs), the **analytics dashboard** (KPIs,
charts, drill-down, live polling), and the **booking entry form** (create/edit, live GST/
commission calculation, capacity warnings, a printable voucher). Feature modules (hotel/room/rate-
plan management, invoicing, settlements) land on top of this in later steps.

## Tech stack

- **Backend:** PHP 8.2+, a custom lightweight MVC (no framework), PSR-4 autoloading via Composer.
- **Database:** MySQL 8 / MariaDB, accessed only through PDO prepared statements.
- **Frontend:** HTML5, a hand-written CSS design system (no Bootstrap/Tailwind), vanilla
  JavaScript (ES modules, no build step). GSAP + ScrollTrigger, Alpine.js, and Chart.js are
  loaded from CDN.
- **Email:** PHPMailer over SMTP. **Scheduling:** PHP CLI scripts under `cron/`, run by system cron.

## Project layout

```
public/            The only web-accessible folder (docroot). index.php is the front controller.
  assets/js/         app.js, api.js, ui.js, confirm.js, dashboard.js, booking-form.js,
                     booking-list.js, format.js, animations.js, charts.js — ES modules, no build step.
  assets/css/        design-system.css, components.css, app.css (loaded everywhere), print.css
                     (loaded only by the print layout).
app/
  Core/            Router, Database (PDO), Request, Response, View, Session, Csrf, Auth,
                   Permission, RoleLevel, Icons, Validator, Mailer, Model, Migration, Migrator,
                   Seeder, App (service + current-request registry).
  Controllers/      AuthController, BookingController, DashboardController, HomeController,
                    HotelFilterController, NotificationController, SearchController.
  Models/           Booking, plus per-table models extending Core/Model as writes land.
  Services/         BookingCalculator (the authoritative money math), plus InvoiceService,
                    CommissionService, ... as those modules land.
  Middleware/        AuthMiddleware, RoleMiddleware(minLevel), HotelScopeMiddleware.
  Views/
    layouts/           public.php, admin.php (the app shell), auth.php, print.php (voucher).
    partials/          sidebar, topbar, bottom-tab-bar, confirm-dialog, toasts, breadcrumbs,
                       empty-state, skeleton, nav-public, footer-public, head-meta,
                       admin/ (chart-body, chart-restricted, booking-room-line, booking-calc-summary).
    pages/             public/, auth/, admin/ (dashboard, bookings/), errors/.
  Helpers/           Global helper functions (money, gst, fy_label, sanitize, icon, can,
                     role_at_least, old, old_array, form_errors, ...).
config/             app.php, database.php, mail.php, auth.php, permissions.php (role -> module ->
                    action matrix — the only config file NOT read from .env).
database/
  migrations/        27 numbered migration files — see "Database schema" below.
  seeders/           RoleSeeder, OtaSeeder, SuperAdminSeeder, HotelSeeder, BookingSeeder,
                     DatabaseSeeder.
cron/               daily_digest.php, weekly_digest.php, retry_emails.php, purge_logs.php.
routes/             web.php — route definitions.
cli                 CLI entry point: php cli migrate | migrate:rollback | migrate:fresh |
                    migrate:status | seed.
```

## Setup

1. **Requirements:** PHP 8.2+, Composer, MySQL 8 / MariaDB. The landing page itself doesn't need
   a database, but the schema/migrations below do.

2. **Install dependencies** (pulls in PHPMailer and generates the Composer autoloader):

   ```bash
   composer install
   ```

   If Composer isn't installed yet, the app still runs: `app/bootstrap.php` falls back to a
   manual PSR-4 autoloader for the `App\` namespace. `composer install` is only required before
   using `App\Core\Mailer` (PHPMailer).

3. **Configure environment:**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` with your database and mail credentials. `APP_KEY` isn't consumed by anything yet
   but is reserved for the encryption/signing needs of upcoming modules.

4. **Create the database, then migrate and seed:**

   ```bash
   # create an empty database matching DB_DATABASE in .env (e.g. `hotezo`) first, then:
   php cli migrate
   php cli seed
   ```

   `php cli seed` prints the Super Admin's email and a one-time generated password to the
   console — copy it now, it is stored only as a bcrypt hash and never shown again. Override it
   in advance with `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` in `.env` if you want fixed
   credentials. See [`cli`](cli) for the full command list
   (`migrate:rollback`, `migrate:fresh`, `migrate:status`). Seeding also generates ~150-250
   sample bookings spread across the last 6 months (`BookingSeeder`) so the dashboard has real
   data to show — it's skipped automatically on repeat `seed` runs once any bookings exist.

5. **Run the dev server** from the project root:

   ```bash
   php -S localhost:8000 -t public
   ```

   Visit `http://localhost:8000` — you should see the Hotezo landing page rendered with the full
   design system (glass cards, gradient buttons, animated KPI stat cards).

   In production, point your web server's docroot at `public/` and enable `mod_rewrite`
   (an `.htaccess` is already in place for Apache) so all requests route through `index.php`.

6. **Log in** at `http://localhost:8000/login` with the Super Admin credentials `php cli seed`
   printed, and you'll land on the real analytics `/dashboard` — see "Authentication &
   authorization" and "Dashboard" below.

## Design system

Dark-mode-first, glassmorphism, gradient-driven — think Linear/Stripe/Vercel dashboards, but
warmer. Tokens, resets, and the `.glass` primitive live in
[public/assets/css/design-system.css](public/assets/css/design-system.css); reusable components
(buttons, inputs, cards, KPI stat cards, data tables, modals, toasts, tabs, badges, sidebar,
topbar, breadcrumbs, empty states, skeleton loaders) live in
[public/assets/css/components.css](public/assets/css/components.css). Both are pulled in by
[public/assets/css/app.css](public/assets/css/app.css), which also holds page-level layout.

Toggle the theme with the sun/moon button in the nav — the choice persists via `localStorage`
under the `hotezo-theme` key, and defaults to the visitor's OS preference otherwise.

## Database schema

27 tables, all InnoDB, all with the standard audit columns (see Conventions below). Numbered
migration files in [database/migrations/](database/migrations/) create them in this dependency
order — each file's doc comment explains any non-obvious modeling decision. `0026`/`0027` are
`ALTER TABLE` migrations on top of the original 25 (added for the auth/authorization system, see
below): `0026` adds `hotel_id` to `user_permissions` so overrides can be hotel-scoped, `0027` adds
`failed_login_attempts`/`locked_until` to `users` for login rate-limiting.

| Area | Tables |
| --- | --- |
| Identity & access | `roles`, `users`, `user_hotels`, `user_permissions` |
| Inventory | `hotels`, `rooms`, `rate_plans`, `otas` |
| Bookings & money | `bookings`, `settlements` |
| Invoicing | `invoice_number_sequence`, `service_invoice_number_sequence`, `company_compliance_details`, `invoice_settings`, `guest_invoices`, `commission_invoices`, `service_invoices`, `invoices` |
| Notifications & email | `notification_settings`, `notification_logs`, `email_templates`, `email_queue`, `invoice_email_logs` |
| Logs | `booking_voucher_logs`, `audit_logs` |

Worth knowing before extending it:

- **`invoices` is a unified ledger**, not a fourth invoice type. Every `guest_invoices` /
  `commission_invoices` / `service_invoices` row that gets issued should also get a row here
  (`invoice_type` + `source_id`) so there's one place to query/report across all three. Since
  `source_id` can point at three different tables, it isn't a real foreign key —
  `invoice_email_logs` links to `invoices.id` instead of the type-specific tables for that reason.
- **`created_by`/`deleted_by` have no FK constraint.** Enforcing one on every table's audit
  columns would force a strict creation order against `users` everywhere and turn hard-deleting a
  user (the future Trash module) into a cascading mess. They're an audit trail, not a relational
  integrity requirement — see `App\Core\Migration::auditColumns()`.
- **Two nullable-`hotel_id` sequence/override tables** (`service_invoice_number_sequence`,
  `email_templates`) use `hotel_id IS NULL` to mean "global/default". MySQL and MariaDB treat
  every `NULL` as distinct for `UNIQUE KEY` purposes, so the DB alone won't stop two global rows
  for the same key — allocate/create through a SELECT-then-INSERT, not an INSERT relying on a
  duplicate-key error. Each migration file's comment flags this where it applies.
- **`roles.level` runs 0 (lowest authority) to 5 (highest)** — `5 = super_admin` down to
  `0 = read_only`. See "Authentication & authorization" below for the full role table.
- **`bookings.rooms` is a JSON array** of room lines — `room_type`, `rate_plan_id` (nullable),
  `adults`, `children`, `quantity`, `nightly_rate` — since one booking can span multiple room
  types. Nights lives once at the booking level (`bookings.nights`), not per line, since check-in/
  check-out apply to the whole stay. See "Booking entry form" below for how these get computed.

## Authentication & authorization

### Roles

10 roles across 5 levels (higher level = more power). Level alone doesn't fully determine access
— the four level-2 roles differ by *module*, not seniority; see the permission matrix below.

| Role | Level | Scope |
| --- | --- | --- |
| `super_admin` | 5 | Bypasses every rule. Full system access. |
| `admin` | 4 | Full management of users, hotels, and OTAs — across *all* hotels. |
| `hotel_manager` | 3 | Full operational control, but only for hotels they're assigned to. |
| `revenue_manager` | 2 | Financial data and revenue reports. |
| `ota_manager` | 2 | OTA relationships and commissions. |
| `reservation_manager` | 2 | Bookings and guest invoices. |
| `accounts` | 2 | Settlements, billing, and invoicing. |
| `front_desk` | 1 | Creates bookings, generates invoices, handles check-in/check-out. |
| `reception` | 1 | Creates bookings. **Default role for new users** (`config('auth.default_role')`). |
| `read_only` | 0 | View-only — no create, edit, or delete anywhere. |

`admin` (level 4) and `super_admin` (level 5) see every hotel; everyone else is restricted to the
hotels listed in `user_hotels` for that user (`App\Core\Auth::hasGlobalHotelAccess()`).

### Login

`GET /login` / `POST /login` / `POST /logout` (`app/Controllers/AuthController.php`,
[app/Views/pages/auth/login.php](app/Views/pages/auth/login.php)). Split-screen glassmorphism —
an animated gradient hero on the left (`layouts/auth.php`, no nav/footer chrome), the form on the
right. Behavior:

- **Bcrypt** password verification against `users.password_hash`.
- Blocks login for `status != 'active'` or `is_deleted = 1` accounts, with the *same* generic
  "email or password incorrect" message as a wrong password — avoids revealing which accounts
  exist or are disabled.
- **Rate-limited**: `config('auth.max_login_attempts')` (default 5) failures locks the account for
  `config('auth.lockout_minutes')` (default 15) via `users.failed_login_attempts` /
  `locked_until`. This is account-scoped, not IP-scoped — simple and effective, but it does mean
  someone who only knows a valid email can lock that account out; add IP throttling too if that
  becomes a real concern.
- **Remember me**: a `userId.token` cookie (`Auth::REMEMBER_COOKIE`), SHA-256 hash of the token
  stored in `users.remember_token`, verified with `hash_equals()`. Checked once per request via
  `Auth::attemptRememberLogin()` in `app/bootstrap.php`, cleared (cookie + DB) on logout.
- CSRF-protected, flashed friendly errors, `old('email')` repopulates the form on failure.

### Middleware

- **`AuthMiddleware`** — must be logged in, else redirect to `/login` (or `401` for AJAX/JSON).
- **`RoleMiddleware(minLevel)`** — coarse route gate on role level, e.g.
  `[RoleMiddleware::class, RoleLevel::ADMIN]`. Good for "must be at least X"; can't by itself
  distinguish same-level roles (that's what `can()` is for).
- **`HotelScopeMiddleware`** — sets `$request->scope('hotel_ids')` to `null` (unrestricted, levels
  4-5) or the caller's actual hotel ID array (possibly empty) for everyone else. Controllers use
  this to filter every hotel-scoped query.

### Permissions: the `can()` helper

`App\Core\Permission::check($module, $action, $hotelId = null)`, exposed as a `can()` global
helper for use directly in views:

```php
<?php if (can('bookings', 'create')): ?>
  <a href="..." class="btn btn-primary">+ New Booking</a>
<?php endif; ?>
```

Resolution order:

1. **Super Admin bypass** — level 5 always passes.
2. **Hotel scope** — if `$hotelId` is given and the caller isn't level 4+, it must be in their
   `user_hotels`, or the check fails outright.
3. **Per-user override** — `user_permissions` (`permission_key` = `"{module}.{action}"`), checked
   hotel-specific first, then global (`hotel_id IS NULL`) as a fallback.
4. **Role default** — `config('permissions.php')`, a `role -> module -> [actions]` matrix mirroring
   the role table above.

`role_at_least(int $level)` is the equivalent level-only guard, e.g.
`role_at_least(RoleLevel::ADMIN)`. Neither directive requires Blade — this project uses plain PHP
templates, so `can()`/`role_at_least()` are the "`@can`/`@role`" of this codebase.

**Role-management rule**: `Permission::canManageRoleLevel($targetRoleLevel)` enforces "a user can
only create/manage users with a role level lower than their own" (Super Admin exempt). No user
CRUD UI exists yet — this is ready for the Users module to call once it lands.

### Protected routes

`GET /dashboard` is gated by all three middleware (`AuthMiddleware`,
`[RoleMiddleware::class, RoleLevel::READ_ONLY]`, `HotelScopeMiddleware`) — see "Dashboard" below
for what it actually renders.

## App shell

Every admin page renders inside `layouts/admin.php`: sidebar + topbar + content, wired once so
individual pages only need to supply their own content and a `pageTitle`/`active` key.

### Sidebar

`partials/sidebar.php` — 10 possible nav items (Dashboard, Bookings, Hotels, OTAs, Invoices,
Reports, Users, Emails, Trash, Settings), each gated by `can($module, 'view')` except Dashboard
(always visible) and Trash (`role_at_least(RoleLevel::SUPER_ADMIN)` — hard-delete access is
absolute, deliberately *not* run through the overridable `can()` matrix). Items without a real
route yet stay `aria-disabled` placeholders even when visible, same convention as the rest of the
still-unbuilt modules. Icons come from `App\Core\Icons` (`icon()` helper) — a small inline-SVG
registry so no icon font/CDN is needed.

Collapse (desktop) via the button at the bottom — state persists in `localStorage`
(`hotezo-sidebar-collapsed`); collapsed links fall back to a CSS-only tooltip (`data-tooltip`,
shown on hover). Below 900px the sidebar becomes a full slide-over drawer instead (hamburger in
the topbar or "More" in the bottom tab bar opens it, `.sidebar-backdrop` closes it) with a
`bottom-tab-bar` (Home/Bookings/Hotels/Invoices/More) for quick thumb-reach access.

### Topbar

`partials/topbar.php`, all four pieces genuinely wired (not decorative):

- **Hotel filter** — a dropdown listing the hotels the user is allowed to see (all of them for
  `admin`+, just their `user_hotels` otherwise), posting to `POST /hotel-filter`. The selection is
  session-persisted and layered on top of the permission-based scope by `HotelScopeMiddleware`
  (it can only *narrow* that scope, never widen it — see `HotelFilterController`). Every
  hotel-scoped controller reads the effective set from `$request->scope('hotel_ids')`.
- **Theme toggle** — sun/moon SVGs cross-faded via CSS (`[data-theme]` + a
  `prefers-color-scheme` fallback for no explicit choice); toggling briefly adds a
  `.theme-transitioning` class to `<html>` so every element's colors animate together instead of
  flipping instantly.
- **Notifications bell** — `GET /notifications/count` (peek, doesn't mark anything seen, safe to
  call on every page load) and `GET /notifications` (full list, marks seen) against
  `notification_logs`. No feature writes to that table yet, so it'll show an honest empty state
  today — the wiring is real and ready for when bookings/invoices start logging there.
- **Search** — debounced `GET /search?q=` against hotel name/city (the one real, browsable entity
  so far), scoped to the caller's hotels. Extend `SearchController` as more entities get list
  views.
- **Profile menu** — name, email, role badge, a placeholder Profile link, and Sign out.

### Confirm dialogs

One shared modal (`partials/confirm-dialog.php` + `public/assets/js/confirm.js`) for destructive
actions. Two ways to trigger it:

```html
<!-- Declarative — for delete forms -->
<form method="POST" action="/hotels/123" data-confirm-submit
      data-confirm-title="Delete hotel?" data-confirm-message="This can't be undone.">
```

```js
// Programmatic — for JS-driven flows
const ok = await confirmDialog({ title: 'Delete hotel?', message: "This can't be undone." });
```

### Everything else

Toasts (`toast()` / `window.Hotezo.toast()`) are wired globally via `partials/toasts.php` in every
layout and auto-render flashed session messages on load. The content area's direct children
animate in on load (GSAP stagger, `initPageEnter('[data-animate], .app-content > *')` — admin
pages get this automatically without needing to tag every element). Theme, sidebar-collapse, and
the hotel filter all persist (`localStorage` for the first two, server-side session for the
filter) — see `App\Core\Session` / `localStorage` keys `hotezo-theme` and
`hotezo-sidebar-collapsed`.

## Dashboard

`GET /dashboard` (`app/Controllers/DashboardController.php::index`) renders a skeleton-first shell
— every KPI/chart/table shows a shimmer placeholder immediately. `GET /dashboard/data` (same
middleware) returns the actual JSON, fetched by `public/assets/js/dashboard.js` via `api.js` and
used to populate everything in place: count-up KPI values, Chart.js instances, and table rows.
Every query in `DashboardController` is scoped by the same `$request->scope('hotel_ids')`
`HotelScopeMiddleware` sets for the rest of the app, further narrowed (never widened) by an
optional `?hotel_id=` drill-down param — see "Drill-down" below.

### KPIs

8 cards, each comparing **month-to-date vs. the same day-range last month** (so a partial current
month is never unfairly compared against a full previous one): Total Bookings, Total Revenue,
Hotel Earnings, Commissions & Taxes, Total Guests, Avg Guests/Booking, OTA Bookings, Total Hotels.
Total Hotels is the odd one out — it compares *cumulative* hotel count as of now vs. as of the
equivalent date last month (portfolio growth), not a period sum like the other seven.

### Charts

All five pull from the trailing 6-month window (not the KPIs' MTD window, so there's enough data
to plot): Monthly Booking Trend (line/area), Revenue by OTA Source (donut), Room Type Distribution
(horizontal bar), Top 5 Hotels by Earnings (bar), OTA vs Direct (small donut). Chart instances are
created once and updated in place on every poll/drill-down (`updateChartData()` in `charts.js`) —
never destroyed and recreated, so there's no flicker.

`Room Type Distribution` decodes `bookings.rooms` JSON in PHP rather than a SQL-side `GROUP BY`,
because this project's MariaDB (10.4) predates `JSON_TABLE` (MariaDB 10.6+ / MySQL 8+). Revisit
if the deployment target's DB version changes.

### Financial gating

Total Revenue, Hotel Earnings, and Commissions & Taxes (KPIs), Revenue by OTA Source and Top 5
Hotels by Earnings (charts), and the entire per-hotel breakdown table are only rendered — server-
side, not just hidden client-side — when `can('reports', 'view')` is true. `front_desk` and
`reception` (no `reports` permission by default) see a "Restricted" placeholder in place of each
gated KPI/chart, no breakdown table at all, and Recent Bookings without its amount column. This is
narrower than the `reports` module already gates via `config/permissions.php` and doesn't need any
new plumbing.

### Drill-down

Click a row in the per-hotel breakdown table to filter the *entire* dashboard (KPIs, charts,
tables) to just that hotel; click the same row again, or the banner's "Clear" button, to go back.
This is a page-local `?hotel_id=` query param on `/dashboard/data` — separate from the topbar's
session-persisted hotel filter (see "App shell" above). `DashboardController::effectiveHotelIds()`
only lets the drill-down *narrow* whatever the user is already permitted to see; requesting a
hotel outside that scope is silently ignored (falls back to the normal scope), not a 403 — checked
directly against the running app, not just read from the code.

### Live updates & empty states

`dashboard.js` polls `/dashboard/data` every 15 seconds so new bookings show up without a reload,
pausing while the tab is hidden (Page Visibility API) and resuming when it's visible again. If a
scope has zero bookings in the 6-month window, the whole KPI/chart/table block is replaced by one
centered empty state (`has_data: false` in the JSON) rather than a wall of zeros; each individual
chart also falls back to its own small empty state if its own dataset happens to be empty while
others aren't.

## Booking entry form

`GET /bookings` (list), `GET /bookings/create` / `POST /bookings` (new), `GET /bookings/{id}/edit`
/ `POST /bookings/{id}` (edit), `GET /bookings/{id}/voucher` (printable) — all in
`app/Controllers/BookingController.php`. One rich glass form (not a wizard) serves both create and
edit, with a sticky **Calculation Summary** panel on the right that updates live as you type.

### The money math: `App\Services\BookingCalculator`

Every figure — nights, room-rent subtotals, GST, TDS, TCS, both commissions, GST-on-commission,
hotel earning, hotel/Hotezo collection split — is computed by this one service from the submitted
room lines. `public/assets/js/booking-form.js` mirrors the exact same formulas for the live
preview, but **the server never trusts that preview**: `BookingController::save()` recomputes
everything via `BookingCalculator::calculate()` from the raw submitted room lines before writing a
single row. `BookingSeeder` uses the same service too, so seeded and form-created bookings are
priced identically.

- **GST slab (5% vs 18%) is applied per room line**, against that line's own nightly rate — not
  against the booking's combined total. That matches how Indian hotel GST actually works (the slab
  depends on each room type's declared tariff), and one booking can mix a budget room and a suite
  that sit in different slabs.
- `hotel_gst` is guest-facing (added on top of room rent, not a deduction); TDS, both commissions,
  and GST-on-commission are what actually reduce `hotel_earning`. TCS is tracked but not deducted
  — see the class docblock for the full reasoning, kept consistent with the dashboard aggregates
  already built on this data.

### Fields worth knowing

- **Booking ID** is editable, not read-only — pre-filled with a suggested `HTZ-{year}-{seq}` (the
  next unused number in that pattern), but the user can overwrite it with e.g. an OTA's own
  reference. `GET /bookings/check-id` backs the 500ms-debounced tick/cross next to the field;
  `exclude_id` is passed in edit mode so a booking doesn't flag its own ID as taken.
- **Property (hotel) is only editable when creating.** Once a booking exists it's rendered
  disabled — changing hotels mid-edit would invalidate the room lines' room types and rate plans,
  so the form just doesn't allow it.
- **Rooms** is a repeater (`GET /bookings/rooms?hotel_id=` feeds it room types with their
  aggregated capacity limits, plus that hotel's rate plans). Picking a room type suggests a
  nightly rate from that type's `base_price`; picking a rate plan overrides it with the plan's own
  price; the rate stays editable either way. Adults/children over a room type's `max_adults`/
  `max_children` show a non-blocking amber warning (client-side only — capacity is a warning, not
  a hard rule, per spec).
- **OTA Source** is a real `otas` table dropdown, not a hardcoded list — `Walk-in` and
  `Direct Booking` are themselves 0%-commission rows in that table (so every booking has a
  non-null `ota_id` regardless of channel; `bookings.source` is the actual OTA-vs-direct
  discriminator, a distinction that mattered when it turned out the dashboard's original OTA-split
  logic had used `ota_id IS NOT NULL` and always read 100% OTA — see the dashboard commit history).

### Permissions, voucher, and audit trail

Create/edit both call `can('bookings', $action, $hotelId)` — hotel-scoped like everywhere else, so
a `reception` user (create-only per `config/permissions.php`) gets a real `403` on
`/bookings/{id}/edit`, and posting a `hotel_id` outside their `user_hotels` on create is rejected
server-side even if the client is tampered with. Every create/update writes one `audit_logs` row
(`booking.created` / `booking.updated`, with the full new values); status transitions into
`checked_in`/`checked_out`/`cancelled` stamp the matching `*_at` timestamp exactly once, on the
request that actually changes into that status.

`GET /bookings/{id}/voucher` renders a print-optimized page (`layouts/print.php` + `print.css`,
`window.print()` — no PDF generation) and logs one `booking_voucher_logs` row
(`action = 'generated'`) each time it's viewed.

**Module 16 stub:** booking creation/update has a marked spot (right after the audit log write in
`BookingController::save()`) for queuing guest/hotel notifications once the email pipeline exists.
Nothing sends today — the schema (`email_queue`, `notification_logs`) and the topbar's
notifications bell are already real and waiting for a writer.

## Bookings list

`GET /bookings` renders a skeleton-first shell (filter bar, 4 stat cards, table); `GET
/bookings/data` (`public/assets/js/booking-list.js`, same middleware) returns the actual filtered/
paginated JSON. Row click fetches `GET /bookings/{id}/detail` into a slide-over drawer with the
full financial breakdown.

### Filters

Hotel, OTA, Status, check-in date range, and a keyword search (guest name / booking ID / mobile,
debounced 400ms) all combine with `AND` in `BookingController::buildFilterWhere()`. Every filter
change writes to the URL via `history.replaceState` and re-fetches — reload the page, or send
someone the URL, and you get back the exact same filtered view. Date range filters on
`checkin_date` (what's happening when), not `booking_date` (when it was booked), as the more
useful axis for an operational list.

The filter bar's own **Hotel** dropdown is intersected with whatever the topbar's global hotel
filter already narrowed the request to (`BookingController::filterableHotels()`) — so it only ever
offers hotels that would actually return rows, rather than a filter combination that silently
combines to zero results. `effectiveHotelIds()` then narrows (never widens) that scope further by
whatever the dropdown picked, same "narrow-only" pattern as the dashboard's drill-down.

### Stats strip

Page Bookings/Revenue/Guests are literal sums of the rows on the *current page* (not gated behind
`can('reports', 'view')` — they're just a sum of the same per-row Amount everyone with
`bookings.view` can already see, so summing them exposes nothing new). Total Matching Filters is
the query's real `COUNT(*)`, independent of pagination.

### OTA "logos"

No real OTA brand marks are bundled — no image pipeline, and reusing actual logos without license
is worth avoiding regardless. `otaBadgeColor()` in `format.js` picks a deterministic color from the
design system's palette based on the OTA name, rendered as a colored initial next to the name.
Same OTA always gets the same color; no asset requests, no trademark risk.

### Financial gating

Same rule as the dashboard and the booking form: the **Hotel Earning** column (and the drawer's
entire financial-breakdown section — GST, TDS, TCS, both commissions, collections) only render —
server-side, the column header included — when `can('reports', 'view')`. The per-row **Amount**
column stays visible to anyone who can view bookings at all, since it's the same operational
figure front desk needs to collect payment, not a portfolio-level margin figure.

## Hotel management

`GET /hotels` is a server-rendered glass card grid (hero image, city, room count, booking count,
commission %, status) — deliberately *not* the AJAX/JSON/pagination treatment the bookings list
gets, since hotel counts are small and nothing was asked for in the way of filtering. Clicking a
card opens `GET /hotels/{id}`, a single tabbed hub page: **Details, Rooms, Rate Plans, Inventory,
Bookings, Guests, Invoices, Staff, Reports, Settings**. Every tab a role can't reach is omitted
server-side, not just hidden — same convention as the dashboard's reports-gated figures.

### Tabs are server-rendered, switched client-side

All ten tabs' content renders on the same request; `ui.js`'s existing `initTabs()` toggles which
`[data-tab-panel]` is visible, no extra fetch per click. `hotel-hub.js` layers two things on top:
it reads `?tab=` on load and clicks the matching tab (so redirecting back from a room/rate-plan
save reopens on the right tab instead of bouncing to Details), and it rewrites `?tab=` as the user
clicks between tabs (so a refresh — or a shared link — lands where they left it).

### Rooms & Rate Plans: modal CRUD, not sub-pages

Each existing room/rate plan gets its own pre-filled edit modal (`partials/admin/room-modal.php`,
`rate-plan-modal.php`) rather than one shared modal populated by JS — hotel room counts are small
enough that this doesn't bloat the page, and it means zero JS is needed to open an edit form with
the right values already in it. Forms POST and redirect back to the same tab; there's no
inline/AJAX save here, unlike the bookings list.

### Bookings tab reuses the bookings list — literally

`partials/admin/bookings-embed.php` is the bookings list's filter bar (minus the now-redundant
Hotel dropdown), stats strip, table, and drawer, and it's driven by the *same*
`booking-list.js` and the *same* `GET /bookings/data` endpoint as the standalone `/bookings` page.
A `data-locked-hotel-id` wrapper tells the script to force that hotel into every query and skip
URL-state syncing (the embedded view shares the hub's URL with the tab param — letting it rewrite
the query string would silently drop `?tab=bookings` and knock a refresh back to Details).

### Guests: derived, not stored

There's no `guests` table — guests only exist as fields on `bookings`. The tab runs
`SELECT ... GROUP BY guest_mobile` (mobile is the one reliably-unique guest identifier captured
today), with `MAX()` wrapping every non-grouped column so the query stays valid under
`ONLY_FULL_GROUP_BY`. The spec's **Company** column has no data source anywhere in the schema — it
renders as `—` rather than inventing a value, with a one-line note in the tab explaining why.

### Invoices & Staff: real queries, naturally empty today

Both tabs query real tables (`invoices`, `user_hotels` joined to `users`/`roles`) — there's no
placeholder logic. They render genuine empty states right now because nothing populates
`invoices` yet (invoice generation is a future module) and no staff have been assigned to hotels
in the seed data, not because the tabs are stubbed.

### Inventory & Settings: honest placeholders

Both are explicitly out of scope for this module. Inventory gets a decorative blurred calendar
grid behind a "Coming Soon" badge (`partials/admin/coming-soon.php`, `calendar: true`) so the tab
still looks intentional rather than broken; Settings gets the same badge without the teaser.

### File uploads

`App\Core\FileUpload` is new, reusable Core infrastructure (mirrors `Migration`/`Seeder` as a
small first-party primitive) — validates actual file content via `finfo` (never the client-sent
mime type or filename), enforces a 5MB cap, and always writes under a fresh UUID name to
`public/assets/uploads/hotels/{hotel_id}/`. The hero image and gallery are the first upload flow
in the app; nothing before this needed one.

### "Hotel Admin" maps to `hotel_manager`

The product brief's "Hotel Admin edits only their own hotel" describes a role that doesn't exist
by that name in the seeded 10-role set — it's exactly what `hotel_manager` (level 3) already does
in `config/permissions.php` (`view`, `edit`; no `create`, no `delete`), so no new role was added.
Both the create-hotel gate and the delete button (and its route, independently, in case someone
scripts a POST directly) check `can('hotels', 'create' | 'delete')`, which only the `admin` role
grants.

### Cascading soft delete

`App\Services\HotelService::delete()` soft-deletes the hotel and cascades to exactly the five
tables the spec named — rooms, bookings, guest invoices, staff assignments (`user_hotels`), rate
plans — each `WHERE hotel_id = ? AND is_deleted = 0`, so already-deleted rows keep their original
`deleted_at`/`deleted_by` instead of being overwritten. It's a new, from-scratch pattern (no prior
cascade delete existed anywhere in the codebase to mirror) and it's the first `Service` whose job
is orchestration rather than money math.

## Conventions

- All money is stored as `DECIMAL(12,2)` INR and formatted with the `money()` helper (Indian
  digit grouping, e.g. `₹1,00,000.00`).
- Every table carries `id` (UUID/`CHAR(36)`, matching the `uuid()` helper), `created_at`,
  `updated_at`, `created_by`, `is_deleted`, `deleted_at`, `deleted_by`, `owner_role`,
  `visibility_scope`. Soft delete everywhere; hard delete only from the (future) Trash module.
- Every write goes through a `Model`; every business calculation goes through a `Service`;
  controllers stay thin.
- Every form is CSRF-protected (`csrf_field()` / `Csrf::verify()`); every query is a prepared
  statement (`App\Core\Database`); all output is escaped with `e()`.
- Non-admin users (level < `RoleLevel::ADMIN`) are scoped to their assigned hotels via
  `HotelScopeMiddleware` and `$request->scope('hotel_ids')` — see "Authentication &
  authorization" above.

## What's next

User management (create/edit users — enforcing `Permission::canManageRoleLevel()`, and giving the
Hotel hub's Staff tab a way to actually assign someone rather than only list existing
`user_hotels` rows), the Inventory calendar, settlements, and PDF invoice generation (which would
finally give the Hotel hub's Invoices tab real rows) — each arrives as its own module inside the
app shell.

**OTA list note:** `otas` seeds 10 rows, not 9 — `Hostelworld` (from the original schema-module
spec) and `Hotels.com` (named later, for the booking form) are both kept rather than one replacing
the other, so no historical booking's `ota_id` gets silently orphaned. See
`database/seeders/OtaSeeder.php`.

**Known scaling limitation:** the topbar hotel filter renders every hotel the user can access as a
plain (scrollable) list. Fine today at 3 seeded hotels and fine for any hotel-scoped user (rarely
more than a handful of assigned hotels), but a Super Admin/Admin browsing 1,000+ hotels will need
a searchable picker instead — swap the dropdown's markup in `partials/topbar.php` for something
backed by `SearchController` (or a dedicated endpoint) when that matters; `HotelFilterController`
and the session/scope plumbing underneath don't need to change.
