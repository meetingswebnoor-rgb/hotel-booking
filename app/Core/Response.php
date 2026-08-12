<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Represents an outgoing HTTP response. Controllers/middleware build
 * one of these and the front controller calls send() exactly once.
 */
final class Response
{
    private function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8', ...$headers]);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return new self($body === false ? '{}' : $body, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }

            foreach (self::securityHeaders() as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }

    /**
     * Applies to every response (HTML/JSON/redirect alike) since this
     * is the one place every request funnels through before output —
     * static assets never hit this (Apache serves them directly), so
     * public/.htaccess carries the equivalent headers for those.
     *
     * @return array<string, string>
     */
    private static function securityHeaders(): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), camera=(), microphone=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()',
            'Content-Security-Policy' => Csp::header(),
        ];

        if (Request::isSecureServer()) {
            // 1 year, no includeSubDomains/preload — this app doesn't
            // control every subdomain the parent domain might have.
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        return $headers;
    }
}
