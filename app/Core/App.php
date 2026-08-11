<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny static registry so global helpers (route(), etc.) can reach
 * framework services without a full DI container.
 */
final class App
{
    private static ?Router $router = null;

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    public static function router(): ?Router
    {
        return self::$router;
    }
}
