<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$composerAutoload = BASE_PATH . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    // Composer isn't installed yet — fall back to a manual PSR-4
    // autoloader for the App\ namespace so the app still runs.
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }
    });

    require BASE_PATH . '/app/Helpers/helpers.php';
}

use App\Core\Env;
use App\Core\Session;
use App\Core\View;

Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) config('app.timezone', 'Asia/Kolkata'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');

set_exception_handler(static function (Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string) $e . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (config('app.debug')) {
        echo '<pre style="padding:24px;font-family:monospace;white-space:pre-wrap;background:#0B0B14;color:#F8F8FC;min-height:100vh;margin:0">'
            . e((string) $e)
            . '</pre>';
        return;
    }

    echo View::render('errors/500', [], 'public');
});

if (PHP_SAPI !== 'cli') {
    Session::start();
}
