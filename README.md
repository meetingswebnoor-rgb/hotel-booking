# Hotezo

Hotezo is two products in one platform:

- **A public booking platform** — a global hub listing 1,000+ hotels, each with its own
  responsive, SEO-friendly landing page and a full online booking flow.
- **A back-office management system** — every booking (online or via an OTA like Booking.com,
  MakeMyTrip, Agoda, Airbnb, Expedia, Goibibo) automatically gets OTA commission, Hotezo's
  commission, GST, TDS, and TCS calculated, with settlements, GST-compliant invoices, email
  notifications, and analytics — surfaced instantly across three dashboards per hotel:
  Super Admin, Hotel Admin, and Hotel Manager / Front Desk.

This repository currently contains the **project skeleton**: folder structure, the custom
lightweight MVC core, the design system, and a styled placeholder landing page. Feature modules
(auth, hotels, bookings, commissions, invoices, dashboards) land on top of this in later steps.

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
                   Validator, Mailer, Model, App (service registry).
  Controllers/      Thin controllers.
  Models/           Per-table models extending Core/Model.
  Services/         Business logic (BookingCalculator, InvoiceService, CommissionService, ...).
  Middleware/        AuthMiddleware, RoleMiddleware, HotelScopeMiddleware.
  Views/             layouts/, partials/, pages/, emails/.
  Helpers/           Global helper functions (money, gst, fy_label, sanitize, ...).
config/             app.php, database.php, mail.php — all read from .env.
database/           migrations/, seeders/ (empty until the schema module lands).
cron/               daily_digest.php, weekly_digest.php, retry_emails.php, purge_logs.php.
routes/             web.php — route definitions.
```

## Setup

1. **Requirements:** PHP 8.2+, Composer, MySQL 8 / MariaDB (only needed once a feature module
   that touches the database lands — the landing page works without it).

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

4. **Run the dev server** from the project root:

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

## Conventions

- All money is stored as `DECIMAL(12,2)` INR and formatted with the `money()` helper (Indian
  digit grouping, e.g. `₹1,00,000.00`).
- Every table carries `id`, `created_at`, `updated_at`, `created_by`, `is_deleted`, `deleted_at`,
  `deleted_by`, `owner_role`, `visibility_scope`. Soft delete everywhere; hard delete only from
  the (future) Trash module.
- Every write goes through a `Model`; every business calculation goes through a `Service`;
  controllers stay thin.
- Every form is CSRF-protected (`csrf_field()` / `Csrf::verify()`); every query is a prepared
  statement (`App\Core\Database`); all output is escaped with `e()`.
- Non-super-admin users are scoped to their hotel via `HotelScopeMiddleware` and
  `$request->scope('hotel_id')`.

## What's next

Auth/RBAC (and the `users` table the `Auth` core class already expects), the hotel/booking
schema and migrations, the three role-based dashboards, and the commission/GST/settlement
services — each arrives as its own module on top of this skeleton.
