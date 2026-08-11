<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Tiny PHP-template view engine: renders a page into a layout, with
 * named sections/slots the page can push content into.
 */
final class View
{
    private static array $sections = [];
    private static array $sectionStack = [];

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::renderTemplate("pages/{$template}", $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderTemplate("layouts/{$layout}", [...$data, 'content' => $content]);
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::renderTemplate("partials/{$template}", $data);
    }

    private static function renderTemplate(string $relativePath, array $data): string
    {
        $path = BASE_PATH . '/app/Views/' . $relativePath . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$relativePath}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    public static function section(string $name): void
    {
        self::$sectionStack[] = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        $name = array_pop(self::$sectionStack);
        if ($name !== null) {
            self::$sections[$name] = (string) ob_get_clean();
        }
    }

    public static function yieldSection(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]);
    }
}
