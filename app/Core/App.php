<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny static registry so global helpers (route(), etc.) and view
 * templates can reach framework services without a full DI container.
 */
final class App
{
    private static ?Router $router = null;
    private static ?Request $request = null;

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    public static function router(): ?Router
    {
        return self::$router;
    }

    public static function setRequest(Request $request): void
    {
        self::$request = $request;
    }

    /**
     * The current request, including whatever scope() data middleware
     * has attached to it — available here so partials like the topbar
     * (which don't get the Request passed explicitly) can read it.
     */
    public static function request(): ?Request
    {
        return self::$request;
    }
}
