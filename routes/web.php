<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Core\RoleLevel;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\HotelScopeMiddleware;
use App\Middleware\RoleMiddleware;

/**
 * @return void
 */
return function (Router $router): void {
    $router->get('/', [HomeController::class, 'index'], name: 'home');

    $router->get('/login', [AuthController::class, 'showLogin'], name: 'login');
    $router->post('/login', [AuthController::class, 'login'], name: 'login.submit');
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class], name: 'logout');

    $router->get('/dashboard', [DashboardController::class, 'index'], [
        AuthMiddleware::class,
        [RoleMiddleware::class, RoleLevel::READ_ONLY],
        HotelScopeMiddleware::class,
    ], name: 'dashboard');

    // Further admin/back-office routes register here as those modules land,
    // e.g. gated at a higher level: [[RoleMiddleware::class, RoleLevel::ADMIN]].
};
