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
lightweight MVC core, the design system, a styled placeholder landing page) and the **complete
MySQL/MariaDB schema** (25 tables, migrations, and seed data). Feature modules (auth wiring,
hotel/booking CRUD, commission calculation, dashboards) land on top of this in later steps.

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
app/
  Core/            Router, Database (PDO), Request, Response, View, Session, Csrf, Auth,
                   Validator, Mailer, Model, Migration, Migrator, Seeder, App (service registry).
  Controllers/      Thin controllers.
  Models/           Per-table models extending Core/Model.
  Services/         Business logic (BookingCalculator, InvoiceService, CommissionService, ...).
  Middleware/        AuthMiddleware, RoleMiddleware, HotelScopeMiddleware.
  Views/             layouts/, partials/, pages/, emails/.
  Helpers/           Global helper functions (money, gst, fy_label, sanitize, ...).
config/             app.php, database.php, mail.php — all read from .env.
database/
  migrations/        25 numbered migration files, one table each — see "Database schema" below.
  seeders/           RoleSeeder, OtaSeeder, SuperAdminSeeder, HotelSeeder, DatabaseSeeder.
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
   (`migrate:rollback`, `migrate:fresh`, `migrate:status`).

5. **Run the dev server** from the project root:

   ```bash
   php -S localhost:8000 -t public
   ```

   Visit `http://localhost:8000` — you should see the Hotezo landing page rendered with the full
   design system (glass cards, gradient buttons, animated KPI stat cards).

   In production, point your web server's docroot at `public/` and enable `mod_rewrite`
   (an `.htaccess` is already in place for Apache) so all requests route through `index.php`.

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

25 tables, all InnoDB, all with the standard audit columns (see Conventions below). Numbered
migration files in [database/migrations/](database/migrations/) create them in this dependency
order — each file's doc comment explains any non-obvious modeling decision.

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
- **`roles.level` runs 0 (highest authority) to 5 (lowest)** — `0 = super_admin` down to
  `5 = read_only_viewer`. See `database/seeders/RoleSeeder.php` for all 10 seeded roles.
- **`bookings.rooms` is a JSON array** of room lines (room, rate plan, nightly rate, nights,
  subtotal) since one booking can span multiple physical rooms.

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
- Non-super-admin users are scoped to their hotel via `HotelScopeMiddleware` and
  `$request->scope('hotel_id')`.

## What's next

Auth/RBAC route wiring (login/logout controllers, permission checks), the booking flow and
commission/GST calculation services, the three role-based dashboards, and PDF invoice generation
— each arrives as its own module on top of this schema.

**Known gap to close when Auth/RBAC is wired up:** `App\Core\Auth::login()` currently reads a
single `$user['hotel_id']`, but the schema models hotel assignment as multi-hotel
(`users.assigned_hotels` JSON cache + the `user_hotels` pivot table, per
`users.hotel_assignment_type`). `Auth` was written before this schema existed and needs a small
update — reading from `user_hotels` (or `assigned_hotels`) instead of a nonexistent `hotel_id`
column — once login is actually implemented.
