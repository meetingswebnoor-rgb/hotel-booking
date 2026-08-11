<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Core\Router;

/**
 * @return void
 */
return function (Router $router): void {
    $router->get('/', [HomeController::class, 'index'], name: 'home');

    // Admin/back-office routes are registered here as those modules land,
    // grouped with AuthMiddleware + RoleMiddleware + HotelScopeMiddleware, e.g.:
    //
    // $router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function (Router $router) {
    //     $router->get('/dashboard', [DashboardController::class, 'index'], name: 'admin.dashboard');
    // });
};
