<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Wraps the incoming HTTP request: query/body input, route params,
 * headers, files, and the scoping data middleware attaches (e.g. hotel_id).
 */
final class Request
{
    private array $params = [];
    private array $scope = [];
    private array $jsonBody;

    public function __construct(
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
        private readonly array $files,
        private readonly array $cookies
    ) {
        $this->jsonBody = $this->parseJsonBody();
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    private function parseJsonBody(): array
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';

        if (!str_contains($contentType, 'application/json')) {
            return [];
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($this->post['_method'])) {
            $override = strtoupper((string) $this->post['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    public function all(): array
    {
        return [...$this->query, ...$this->post, ...$this->jsonBody];
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = 'HTTP_' . str_replace('-', '_', strtoupper($key));

        return $this->server[$normalized] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains((string) ($this->server['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest' || $this->isJson();
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function isSecure(): bool
    {
        return self::isSecureServer($this->server);
    }

    /**
     * Static so it's usable before a Request exists (bootstrap.php's
     * HTTPS-enforcement redirect runs before Request::capture()).
     * Trusts X-Forwarded-Proto in addition to HTTPS since Hostinger
     * (and most shared hosts) terminate TLS at a front-end proxy and
     * forward plain HTTP to PHP.
     *
     * @param array<string, mixed>|null $server defaults to $_SERVER
     */
    public static function isSecureServer(?array $server = null): bool
    {
        $server ??= $_SERVER;

        $https = $server['HTTPS'] ?? '';
        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        $forwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';

        return is_string($forwardedProto) && strtolower($forwardedProto) === 'https';
    }

    public function setScope(string $key, mixed $value): void
    {
        $this->scope[$key] = $value;
    }

    public function scope(string $key, mixed $default = null): mixed
    {
        return $this->scope[$key] ?? $default;
    }
}
