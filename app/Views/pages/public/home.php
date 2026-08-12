<?php

use App\Core\View;

/**
 * The public marketing landing page — sections A-M per the brief:
 * navbar (partials/nav-public.php, via layouts/public.php) · hero ·
 * stats band · problem/solution · features · how it works · OTA
 * integrations · roles · live demo · testimonials · pricing · final CTA
 * · footer (partials/footer-public.php). Page-specific visuals live in
 * landing.css/landing.js, loaded only here via the View 'styles'/'scripts'
 * sections — /explore and /hotel/{slug} share the same public layout
 * without paying for this page's CSS/JS.
 *
 * @var bool $isLoggedIn
 * @var string $registerUrl
 * @var string $contactHref
 * @var bool $demoModeEnabled
 */

$features = [
    ['icon' => 'bar-chart', 'title' => 'Smart Dashboard', 'desc' => '8 KPIs plus booking-trend, revenue-by-OTA, room-type, and top-hotel charts, live.'],
    ['icon' => 'sliders', 'title' => 'Auto Financial Engine', 'desc' => 'GST (5%/18%), TDS 0.1%, TCS, OTA & Hotezo commission, hotel earning — computed every time.'],
    ['icon' => 'share', 'title' => 'Multi-Hotel, Multi-OTA', 'desc' => 'Unlimited hotels across Booking.com, MakeMyTrip, Agoda, Airbnb, Expedia, Goibibo, Hotels.com, Direct & Walk-in.'],
    ['icon' => 'home', 'title' => 'Public Booking Pages', 'desc' => 'Every hotel gets a responsive, SEO-friendly landing page and booking flow.'],
    ['icon' => 'file-text', 'title' => '3 Invoice Types', 'desc' => 'Commission (B2B), Service (custom), and Guest (B2C GST) invoices, auto-emailed.'],
    ['icon' => 'trending-up', 'title' => 'Reports', 'desc' => 'Overview, Booking, Revenue, Occupancy, and Guest reports, with CSV export.'],
    ['icon' => 'mail', 'title' => 'Email Automation', 'desc' => 'Templates, a send queue, digests, retries, and per-user notification preferences.'],
    ['icon' => 'users', 'title' => 'Role-Based Access', 'desc' => '10 roles across 5 levels, per-hotel data scoping, and granular permission overrides.'],
    ['icon' => 'trash', 'title' => 'Trash & Audit', 'desc' => 'A soft-delete recycle bin plus a full audit trail for Super Admin.'],
];

$timelineSteps = [
    ['title' => 'Guest books', 'desc' => 'Online direct, or through any connected OTA.'],
    ['title' => 'It reflects instantly', 'desc' => 'Super Admin, Hotel Admin, and Manager dashboards update in real time.'],
    ['title' => 'Front desk runs the stay', 'desc' => 'Check-in/out, and payment marked paid, hold, or disputed.'],
    ['title' => 'Settlement is automatic', 'desc' => 'On checkout, the hotel is settled and Hotezo\'s commission is recorded.'],
];

$otaNames = ['Booking.com', 'MakeMyTrip', 'Agoda', 'Airbnb', 'Expedia', 'Goibibo', 'Hotels.com', 'Hostelworld', 'Direct', 'Walk-in'];
$chipPalette = ['#6366F1', '#06B6D4', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#A855F7'];

$roleCards = [
    [
        'badge' => 'Super Admin',
        'title' => 'Runs the whole platform',
        'desc' => 'Control every hotel, commission rate, user, and settlement from one hub.',
        'bullets' => ['All hotels, all data, no scoping', 'Manage roles, users & permission overrides', 'System-wide audit trail & trash'],
    ],
    [
        'badge' => 'Hotel Admin (Owner)',
        'title' => 'Sees only their hotel',
        'desc' => 'Bookings, earnings, invoices, staff, and reports — for the properties they own.',
        'bullets' => ['Real-time earnings, not just revenue', 'Staff assignment & rate-plan control', 'Invoices generated automatically'],
    ],
    [
        'badge' => 'Front Desk / Manager',
        'title' => 'Runs day-to-day operations',
        'desc' => 'Fast check-in/out, payment status, guest invoices, and printable vouchers.',
        'bullets' => ['Create & edit bookings in seconds', 'Mark payment paid, hold, or disputed', 'One-click printable voucher'],
    ],
];

$testimonials = [
    ['quote' => 'We stopped reconciling OTA payouts by hand — the numbers just match now.', 'name' => 'Sample Hotelier', 'role' => 'Independent property, placeholder quote', 'initials' => 'SH'],
    ['quote' => 'Every front desk shift can see exactly what\'s owed and what\'s settled.', 'name' => 'Sample Front Desk Lead', 'role' => 'Demo account, placeholder quote', 'initials' => 'FD'],
    ['quote' => 'One dashboard instead of six OTA extranets and a spreadsheet.', 'name' => 'Sample Owner', 'role' => 'Multi-property group, placeholder quote', 'initials' => 'SO'],
];

View::section('styles');
?>
<link rel="stylesheet" href="<?= asset('css/landing.css') ?>">
<?php View::endSection(); ?>

<!-- B. HERO -->
<section class="hero hero--landing" data-landing-page data-animate>
  <div class="hero__aurora"></div>
  <div class="hero__shapes">
    <span class="hero__shape hero__shape--1" data-parallax-shape></span>
    <span class="hero__shape hero__shape--2" data-parallax-shape></span>
    <span class="hero__shape hero__shape--3" data-parallax-shape></span>
  </div>

  <div class="container hero__grid">
    <div class="hero__copy">
      <span class="hero__eyebrow glass" data-animate><span aria-hidden="true">✨</span> The booking hub + back office for hotels</span>
      <h1 class="hero__title" data-animate>One platform.<br><span class="gradient-text">Every booking, settled automatically.</span></h1>
      <p class="hero__subtitle" data-animate>List your hotel, take direct &amp; OTA bookings, and let Hotezo auto-calculate every commission, GST, TDS and TCS — down to the exact rupee the hotel earns.</p>

      <div class="hero__actions" data-animate>
        <a href="<?= e($registerUrl) ?>" class="btn btn-primary btn-lg">List Your Hotel</a>
        <a href="#demo" class="btn btn-ghost btn-lg">Book a Live Demo</a>
        <?php if (!$isLoggedIn): ?>
          <a href="<?= route('login') ?>" class="btn btn-ghost btn-lg">Log In</a>
        <?php endif; ?>
      </div>

      <p class="hero__trust text-low" data-animate>1000+ hotels &middot; 9 OTA channels &middot; GST-compliant invoicing</p>
    </div>

    <div class="hero__mockup" data-hero-mockup aria-hidden="true">
      <div class="mockup-card glass">
        <div class="mockup-card__header">
          <span class="mockup-card__dot"></span><span class="mockup-card__dot"></span><span class="mockup-card__dot"></span>
          <span class="mockup-card__title">Hotezo Dashboard</span>
          <span class="mockup-card__live"><span class="mockup-card__live-dot"></span>Live</span>
        </div>
        <div class="mockup-card__toolbar" data-hero-toolbar>
          <span class="mockup-card__btn mockup-card__btn--primary"><?= icon('plus', 'icon icon-xs') ?>New Booking</span>
          <span class="mockup-card__btn mockup-card__btn--icon"><?= icon('calendar', 'icon icon-xs') ?></span>
          <span class="mockup-card__btn mockup-card__btn--icon mockup-card__btn--bell"><?= icon('bell', 'icon icon-xs') ?></span>
        </div>
        <div class="mockup-card__kpis">
          <div class="mockup-kpi" data-hero-kpi>
            <span class="mockup-kpi__label">Total Bookings</span>
            <span class="mockup-kpi__value font-mono" data-countup="1284">0</span>
          </div>
          <div class="mockup-kpi" data-hero-kpi>
            <span class="mockup-kpi__label">Revenue</span>
            <span class="mockup-kpi__value font-mono"><span data-countup="4823600" data-countup-prefix="₹">0</span></span>
          </div>
          <div class="mockup-kpi" data-hero-kpi>
            <span class="mockup-kpi__label">Hotel Earnings</span>
            <span class="mockup-kpi__value font-mono"><span data-countup="3941200" data-countup-prefix="₹">0</span></span>
          </div>
        </div>
        <div class="mockup-card__chart">
          <canvas data-mockup-chart></canvas>
        </div>
        <div class="mockup-card__activity" data-hero-activity>
          <div class="mockup-activity-row" data-hero-activity-row>
            <span class="mockup-activity-row__dot mockup-activity-row__dot--emerald"></span>
            <span class="mockup-activity-row__text">Room 204 checked in</span>
            <span class="mockup-activity-row__time">2m</span>
          </div>
          <div class="mockup-activity-row" data-hero-activity-row>
            <span class="mockup-activity-row__dot mockup-activity-row__dot--amber"></span>
            <span class="mockup-activity-row__text">Booking.com — new reservation</span>
            <span class="mockup-activity-row__time">6m</span>
          </div>
          <div class="mockup-activity-row" data-hero-activity-row>
            <span class="mockup-activity-row__dot mockup-activity-row__dot--primary"></span>
            <span class="mockup-activity-row__text">Invoice #HZ-2291 settled</span>
            <span class="mockup-activity-row__time">14m</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- C. STATS BAND -->
<section class="section" style="padding-top: 0;" aria-label="Platform stats">
  <div class="container">
    <div class="stats-band glass" data-reveal-group>
      <div class="stats-band__item" data-reveal-item>
        <div class="stats-band__value"><span data-countup="1000" data-countup-onscroll data-countup-suffix="+">0</span></div>
        <div class="stats-band__label">Hotels</div>
      </div>
      <div class="stats-band__item" data-reveal-item>
        <div class="stats-band__value"><span data-countup="9" data-countup-onscroll>0</span></div>
        <div class="stats-band__label">OTA Channels</div>
      </div>
      <div class="stats-band__item" data-reveal-item>
        <div class="stats-band__value"><span data-countup="25" data-countup-onscroll data-countup-suffix="+">0</span></div>
        <div class="stats-band__label">Automated Data Modules</div>
      </div>
      <div class="stats-band__item" data-reveal-item>
        <div class="stats-band__value"><span data-countup="10" data-countup-onscroll>0</span></div>
        <div class="stats-band__label">Role Levels</div>
      </div>
    </div>
  </div>
</section>

<!-- D. PROBLEM -> SOLUTION -->
<section class="section">
  <div class="container">
    <div class="problem-solution" data-reveal-group>
      <div class="card glass problem-solution__col" data-reveal-item>
        <span class="badge badge--danger">The problem</span>
        <h2 class="mt-4">OTA bookings are a financial mess.</h2>
        <ul class="problem-solution__pain-list">
          <li><?= icon('alert-triangle', 'icon icon-sm') ?><span>Every OTA takes a different commission — tracked by hand, per booking.</span></li>
          <li><?= icon('alert-triangle', 'icon icon-sm') ?><span>GST slabs differ by room rate, plus TDS and TCS on top.</span></li>
          <li><?= icon('alert-triangle', 'icon icon-sm') ?><span>Reconciling what's actually owed means spreadsheets, every settlement cycle.</span></li>
          <li><?= icon('alert-triangle', 'icon icon-sm') ?><span>No single dashboard shows what the hotel actually keeps.</span></li>
        </ul>
      </div>

      <div class="card glass problem-solution__col" data-reveal-item>
        <span class="badge badge--success">The solution</span>
        <h2 class="mt-4">Hotezo does the math automatically.</h2>
        <p class="text-med mt-2">Every figure below is computed the same way, every time, by
          <code class="font-mono">App\Services\BookingCalculator</code> — never estimated.</p>
        <div class="calc-flow">
          <div class="calc-flow__row"><span class="calc-flow__label">Room Rent</span><span class="calc-flow__value">₹10,000</span></div>
          <div class="calc-flow__arrow">− OTA Commission</div>
          <div class="calc-flow__row"><span class="calc-flow__label">Hotezo Commission</span><span class="calc-flow__value">− ₹1,500</span></div>
          <div class="calc-flow__arrow">− GST · TDS · TCS</div>
          <div class="calc-flow__row calc-flow__row--result"><span class="calc-flow__label">Hotel Earning</span><span class="calc-flow__value">₹7,835</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- E. FEATURES GRID -->
<section class="section" id="features">
  <div class="container">
    <div class="section__header">
      <h2>Everything the back office needs</h2>
      <p class="mt-4">Nine modules, working off the same booking data, with nothing to reconcile by hand.</p>
    </div>

    <div class="feature-grid" data-reveal-group>
      <?php foreach ($features as $f): ?>
        <article class="card card-hover feature-card glass" data-reveal-item>
          <div class="feature-card__icon"><?= icon($f['icon']) ?></div>
          <h3><?= e($f['title']) ?></h3>
          <p><?= e($f['desc']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- F. HOW IT WORKS -->
<section class="section" id="how">
  <div class="container">
    <div class="section__header">
      <h2>How it works</h2>
      <p class="mt-4">From the moment a guest books, to the moment the hotel is paid.</p>
    </div>

    <div class="timeline" data-reveal-group>
      <?php foreach ($timelineSteps as $i => $step): ?>
        <div class="card glass timeline__step" data-reveal-item>
          <div class="timeline__number"><?= $i + 1 ?></div>
          <h3><?= e($step['title']) ?></h3>
          <p class="text-med"><?= e($step['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- G. OTA INTEGRATIONS -->
<section class="section" id="otas" aria-label="OTA integrations">
  <div class="container">
    <div class="section__header">
      <h2>Every channel, one dashboard</h2>
      <p class="mt-4">Connect as many OTAs as you sell through — Hotezo tells them apart automatically.</p>
    </div>
  </div>

  <div class="marquee" data-marquee>
    <div class="marquee__track">
      <?php foreach ([...$otaNames, ...$otaNames] as $i => $name): ?>
        <span class="ota-chip-lg glass">
          <span class="ota-chip-lg__mark" style="background: <?= e($chipPalette[$i % count($chipPalette)]) ?>"><?= e(mb_substr($name, 0, 1)) ?></span>
          <span><?= e($name) ?></span>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- H. BUILT FOR EVERY ROLE -->
<section class="section" id="roles">
  <div class="container">
    <div class="section__header">
      <h2>Built for every role</h2>
      <p class="mt-4">The same booking, three completely different views.</p>
    </div>

    <div class="role-grid" data-reveal-group>
      <?php foreach ($roleCards as $r): ?>
        <div class="card glass role-card" data-reveal-item>
          <span class="badge badge--info role-card__badge"><?= e($r['badge']) ?></span>
          <h3><?= e($r['title']) ?></h3>
          <p class="text-med mt-2"><?= e($r['desc']) ?></p>
          <ul>
            <?php foreach ($r['bullets'] as $bullet): ?>
              <li><?= icon('check', 'icon icon-sm') ?><span><?= e($bullet) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- I. LIVE DEMO -->
<section class="section" id="demo">
  <div class="container">
    <div class="section__header">
      <h2>Try Hotezo now — no signup</h2>
      <p class="mt-4">Jump straight into a real, populated dashboard as any of the three roles above.</p>
    </div>

    <?php if ($demoModeEnabled): ?>
      <div class="demo-grid" data-reveal-group>
        <?= partial('public/demo-login-card', [
            'role' => 'super_admin',
            'label' => 'Super Admin',
            'desc' => 'Sees every hotel, every commission, every setting.',
            'email' => 'superadmin@hotezo.com',
            'password' => 'Super@Hotezo2026',
        ]) ?>
        <?= partial('public/demo-login-card', [
            'role' => 'admin',
            'label' => 'Hotel Admin (Owner)',
            'desc' => 'Scoped to Demo Grand Hotel — bookings, earnings, staff.',
            'email' => 'admin@hotezo.com',
            'password' => 'Admin@Hotezo2026',
        ]) ?>
        <?= partial('public/demo-login-card', [
            'role' => 'manager',
            'label' => 'Hotel Manager',
            'desc' => 'Front-desk view — check-in/out, payments, vouchers.',
            'email' => 'manager@hotezo.com',
            'password' => 'Manager@Hotezo2026',
        ]) ?>
      </div>
    <?php else: ?>
      <div class="card glass" style="max-width: 560px; margin-inline: auto; text-align: center; padding: var(--space-10);">
        <p class="text-med">Live demo access is switched off right now — <a href="<?= route('login') ?>" class="gradient-text">log in</a> with your own account instead.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- J. TESTIMONIALS -->
<section class="section" aria-label="Testimonials">
  <div class="container">
    <div class="section__header">
      <span class="badge badge--neutral mb-4">Sample feedback — placeholder content, not real reviews</span>
      <h2>What running on Hotezo could look like</h2>
    </div>

    <div class="testimonial-grid" data-reveal-group>
      <?php foreach ($testimonials as $t): ?>
        <figure class="card glass testimonial-card" data-reveal-item>
          <blockquote class="testimonial-card__quote">&ldquo;<?= e($t['quote']) ?>&rdquo;</blockquote>
          <figcaption class="testimonial-card__footer">
            <span class="testimonial-avatar" aria-hidden="true"><?= e($t['initials']) ?></span>
            <span>
              <span class="testimonial-card__name" style="display:block;"><?= e($t['name']) ?></span>
              <span class="testimonial-card__role"><?= e($t['role']) ?></span>
            </span>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- K. PRICING / MODEL -->
<section class="section" aria-label="Pricing model">
  <div class="container">
    <div class="card glass pricing-card" data-animate>
      <span class="badge badge--info pricing-card__badge">Commission-based, not seat-based</span>
      <h2>Hotezo earns when you earn.</h2>
      <div class="pricing-card__range">5–50% commission</div>
      <p class="text-med">Set per hotel, configured by your Super Admin — no per-seat licensing fee, no
        surprise line items. The exact same engine that calculates OTA commissions calculates Hotezo's.</p>
      <div class="mt-6">
        <a href="<?= e($contactHref) ?>" class="btn btn-primary btn-lg">Talk to us</a>
      </div>
    </div>
  </div>
</section>

<!-- L. FINAL CTA -->
<section class="section">
  <div class="container">
    <div class="card final-cta" data-animate>
      <h2>Ready to put your hotel on autopilot?</h2>
      <p>List your property, connect your OTAs, and let Hotezo handle every commission and tax
        calculation from the first booking onward.</p>
      <div class="final-cta__actions">
        <a href="<?= e($registerUrl) ?>" class="btn btn-primary btn-lg">List Your Hotel</a>
        <a href="#demo" class="btn btn-ghost btn-lg">Book a Live Demo</a>
      </div>
    </div>
  </div>
</section>

<?php View::section('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
<script type="module" src="<?= asset('js/landing.js') ?>"></script>
<?php View::endSection(); ?>
