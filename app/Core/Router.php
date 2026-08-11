<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Minimal router: GET/POST/PATCH/PUT/DELETE, {param} segments,
 * middleware chains (per-route and via group()), and named routes
 * for URL generation through route().
 */
final class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];

    public function get(string $uri, array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('GET', $uri, $action, $middleware, $name);
    }

    public function post(string $uri, array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('POST', $uri, $action, $middleware, $name);
    }

    public function put(string $uri, array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('PUT', $uri, $action, $middleware, $name);
    }

    public function patch(string $uri, array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('PATCH', $uri, $action, $middleware, $name);
    }

    public function delete(string $uri, array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('DELETE', $uri, $action, $middleware, $name);
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, array|string $action, array $middleware, ?string $name): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = [...$groupMiddleware, ...($group['middleware'] ?? [])];
        }

        $fullUri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        $fullUri = $fullUri === '' ? '/' : rtrim($fullUri, '/');
        $fullUri = $fullUri === '' ? '/' : $fullUri;

        $route = [
            'method' => $method,
            'uri' => $fullUri,
            'action' => $action,
            'middleware' => [...$groupMiddleware, ...$middleware],
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $fullUri;
        }

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compile($route['uri']);

            if (preg_match($pattern, $path, $matches) === 1) {
                $params = array_filter($matches, static fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                return $this->runPipeline($route, $request);
            }
        }

        return Response::html(view('errors/404', [], 'public'), 404);
    }

    private function compile(string $uri): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $uri);

        return '#^' . $pattern . '$#';
    }

    private function runPipeline(array $route, Request $request): Response
    {
        $handler = function (Request $request) use ($route): Response {
            $action = $route['action'];
            [$class, $method] = is_array($action) ? $action : explode('@', $action, 2);
            $controller = new $class();
            $result = $controller->{$method}($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };

        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (Closure $next, array|string $middleware): Closure {
                return function (Request $request) use ($middleware, $next): Response {
                    if (is_array($middleware)) {
                        $class = $middleware[0];
                        $args = array_slice($middleware, 1);
                        $instance = new $class(...$args);
                    } else {
                        $instance = new $middleware();
                    }

                    /** @var MiddlewareInterface $instance */
                    return $instance->handle($request, $next);
                };
            },
            $handler
        );

        return $pipeline($request);
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            return '#';
        }

        $uri = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        return $uri;
    }
}
