<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Csp;
use App\Core\Csrf;
use App\Core\Icons;
use App\Core\Permission;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($cache[$file])) {
            $configPath = BASE_PATH . "/config/{$file}.php";
            $cache[$file] = is_file($configPath) ? require $configPath : [];
        }

        if ($path === null) {
            return $cache[$file];
        }

        $value = $cache[$file];

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], ?string $layout = null): string
    {
        return View::render($template, $data, $layout);
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): string
    {
        return View::partial($template, $data);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool
    {
        return Session::hasFlash($key);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return App::router()?->url($name, $params) ?? '#';
    }
}

if (!function_exists('icon')) {
    function icon(string $name, string $class = 'icon'): string
    {
        return Icons::render($name, $class);
    }
}

if (!function_exists('can')) {
    /**
     * UI/route guard: super-admin bypass -> hotel scope -> per-user
     * override -> role-level default. Use in views to hide elements
     * the current user can't act on, e.g.:
     *   <?php if (can('bookings', 'create')): ?> ... <?php endif; ?>
     */
    function can(string $module, string $action, ?string $hotelId = null): bool
    {
        return Permission::check($module, $action, $hotelId);
    }
}

if (!function_exists('role_at_least')) {
    /**
     * UI/route guard by level, e.g.:
     *   <?php if (role_at_least(RoleLevel::ADMIN)): ?> ... <?php endif; ?>
     */
    function role_at_least(int $level): bool
    {
        return Auth::hasMinLevel($level);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $input = Session::getFlash('_old_input', []);
        $value = $input[$key] ?? $default;

        return is_array($value) ? $default : (string) $value;
    }
}

if (!function_exists('old_array')) {
    /**
     * Array-valued counterpart to old() — for repeater fields like a
     * booking's room lines, where the flashed value is itself an array.
     *
     * @param array<int, mixed> $default
     * @return array<int, mixed>
     */
    function old_array(string $key, array $default = []): array
    {
        $input = Session::getFlash('_old_input', []);
        $value = $input[$key] ?? $default;

        return is_array($value) ? $value : $default;
    }
}

if (!function_exists('form_errors')) {
    /**
     * @return array<string, array<int, string>>
     */
    function form_errors(): array
    {
        return Session::getFlash('_form_errors', []);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * The current request's CSP nonce — attach to any inline <script>
     * that genuinely can't be external (see partials/head-meta.php's
     * theme-flash-prevention script). Response::send() emits the
     * matching Content-Security-Policy header via the same nonce.
     */
    function csp_nonce(): string
    {
        return Csp::nonce();
    }
}

if (!function_exists('sanitize')) {
    function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('sanitize', $value);
        }

        return is_string($value) ? trim(strip_tags($value)) : $value;
    }
}

if (!function_exists('uuid')) {
    function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('money')) {
    /**
     * Format a number as Indian Rupees with Indian digit grouping
     * (1,00,000 not 100,000).
     */
    function money(float|int|string $amount, bool $withSymbol = true): string
    {
        $amount = (float) $amount;
        $negative = $amount < 0;
        $amount = abs($amount);

        [$integer, $decimal] = explode('.', number_format($amount, 2, '.', ''));

        $lastThree = substr($integer, -3);
        $rest = substr($integer, 0, -3);

        if ($rest !== '') {
            $rest = (string) preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest . ',' . $lastThree;
        } else {
            $formatted = $lastThree;
        }

        return ($negative ? '-' : '') . ($withSymbol ? '₹' : '') . $formatted . '.' . $decimal;
    }
}

if (!function_exists('gst')) {
    /**
     * @return array{cgst: float, sgst: float, igst: float, total_tax: float, taxable_amount: float, grand_total: float}
     */
    function gst(float $amount, float $ratePercent = 18.0, string $type = 'intra'): array
    {
        $totalTax = round($amount * $ratePercent / 100, 2);

        if ($type === 'intra') {
            return [
                'cgst' => round($totalTax / 2, 2),
                'sgst' => round($totalTax / 2, 2),
                'igst' => 0.0,
                'total_tax' => $totalTax,
                'taxable_amount' => round($amount, 2),
                'grand_total' => round($amount + $totalTax, 2),
            ];
        }

        return [
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => $totalTax,
            'total_tax' => $totalTax,
            'taxable_amount' => round($amount, 2),
            'grand_total' => round($amount + $totalTax, 2),
        ];
    }
}

if (!function_exists('gst_state_name')) {
    /**
     * The standard GST state-code table (first two digits of any
     * GSTIN). Used to turn a derived code into a real "Place of
     * Supply" label on an invoice rather than just showing the raw
     * digits — shared by the commission and service invoice generators.
     *
     * @return string|null null for an unrecognized/missing code
     */
    function gst_state_name(?string $code): ?string
    {
        static $states = [
            '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab',
            '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi',
            '08' => 'Rajasthan', '09' => 'Uttar Pradesh', '10' => 'Bihar', '11' => 'Sikkim',
            '12' => 'Arunachal Pradesh', '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
            '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal',
            '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh',
            '24' => 'Gujarat', '26' => 'Dadra and Nagar Haveli and Daman and Diu',
            '27' => 'Maharashtra', '28' => 'Andhra Pradesh (Old)', '29' => 'Karnataka',
            '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu',
            '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana',
            '37' => 'Andhra Pradesh', '38' => 'Ladakh',
        ];

        return $code !== null ? ($states[$code] ?? null) : null;
    }
}

if (!function_exists('fy_label')) {
    /**
     * Indian financial year label (April–March) for a given date, e.g.
     * "2025-26" — no "FY " prefix: every `financial_year` column this
     * feeds (commission_invoices, invoices, invoice_number_sequence,
     * service_invoice_number_sequence) is VARCHAR(9), and "FY 2025-26"
     * is 10 characters — it would not have fit. Not caught earlier
     * because nothing called this helper until the commission-invoice
     * module did.
     */
    function fy_label(?string $date = null): string
    {
        $timestamp = $date !== null ? strtotime($date) : time();
        $timestamp = $timestamp === false ? time() : $timestamp;

        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);

        [$start, $end] = $month >= 4 ? [$year, $year + 1] : [$year - 1, $year];

        return "{$start}-" . substr((string) $end, -2);
    }
}

if (!function_exists('amount_in_words')) {
    /**
     * Indian-numbering (lakh/crore) amount-in-words for the "Total in
     * words" line a GST invoice needs — e.g. 1234567.50 becomes
     * "Twelve Lakh Thirty Four Thousand Five Hundred Sixty Seven Rupees
     * and Fifty Paise Only".
     */
    function amount_in_words(float $amount): string
    {
        $amount = round(abs($amount), 2);
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = $rupees === 0 ? 'Zero' : _amount_in_words_indian_grouping($rupees);
        $result = trim($words) . ' Rupees';

        if ($paise > 0) {
            $result .= ' and ' . trim(_amount_in_words_below_hundred($paise)) . ' Paise';
        }

        return $result . ' Only';
    }
}

if (!function_exists('_amount_in_words_below_hundred')) {
    function _amount_in_words_below_hundred(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($n < 20) {
            return $ones[$n];
        }

        return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
    }
}

if (!function_exists('_amount_in_words_indian_grouping')) {
    /**
     * Splits into crore / lakh / thousand / hundred groups (the Indian
     * digit-grouping system: 2-digit groups after the first 3 digits,
     * not the international 3-digit-everywhere grouping) rather than
     * converting the whole number in one pass.
     */
    function _amount_in_words_indian_grouping(int $n): string
    {
        $parts = [];

        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $n %= 1000;
        $hundred = intdiv($n, 100);
        $remainder = $n % 100;

        if ($crore > 0) {
            $parts[] = _amount_in_words_below_hundred($crore) . ' Crore';
        }

        if ($lakh > 0) {
            $parts[] = _amount_in_words_below_hundred($lakh) . ' Lakh';
        }

        if ($thousand > 0) {
            $parts[] = _amount_in_words_below_hundred($thousand) . ' Thousand';
        }

        if ($hundred > 0) {
            $parts[] = _amount_in_words_below_hundred($hundred) . ' Hundred';
        }

        if ($remainder > 0) {
            $parts[] = _amount_in_words_below_hundred($remainder);
        }

        return implode(' ', $parts);
    }
}
