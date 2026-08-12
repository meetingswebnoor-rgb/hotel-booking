<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-request Content-Security-Policy nonce. Generated lazily and
 * cached for the life of the request, so a template that embeds it
 * inline (see partials/head-meta.php) and Response::send() building
 * the CSP header always agree on the same value — same pattern as
 * App\Core\Csrf's per-session token, just per-request instead.
 */
final class Csp
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }

        return self::$nonce;
    }

    /**
     * Builds the Content-Security-Policy header value. Script origins
     * are the exact CDNs the layouts load (cdnjs for GSAP/ScrollTrigger,
     * jsdelivr for Alpine/Chart.js, unpkg for the login page's
     * dotlottie-wc web component) plus 'self' and this request's
     * nonce — no 'unsafe-inline' for scripts. 'wasm-unsafe-eval' is
     * scoped narrowly to WebAssembly compilation (dotlottie's renderer
     * is WASM-based) without granting general eval(). connect-src adds
     * lottie.host (the animation JSON) and cdn.jsdelivr.net — dotlottie-wc
     * pulls its actual WASM binary from jsdelivr at runtime regardless of
     * which CDN served the JS module itself, confirmed via a real
     * browser's securitypolicyviolation events rather than guessed.
     * Style-src
     * keeps 'unsafe-inline' since the views use inline style="" widely;
     * that's a far smaller risk surface than inline script execution.
     */
    public static function header(): string
    {
        $nonce = self::nonce();

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'wasm-unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://api.fontshare.com https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com https://api.fontshare.com https://cdn.fontshare.com",
            "img-src 'self' data: blob:",
            "connect-src 'self' https://unpkg.com https://lottie.host https://cdn.jsdelivr.net",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        return implode('; ', $directives);
    }
}
