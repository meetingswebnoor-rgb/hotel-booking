<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
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

        return (string) ($input[$key] ?? $default);
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

if (!function_exists('fy_label')) {
    /**
     * Indian financial year label (April–March) for a given date.
     */
    function fy_label(?string $date = null): string
    {
        $timestamp = $date !== null ? strtotime($date) : time();
        $timestamp = $timestamp === false ? time() : $timestamp;

        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);

        [$start, $end] = $month >= 4 ? [$year, $year + 1] : [$year - 1, $year];

        return "FY {$start}-" . substr((string) $end, -2);
    }
}
