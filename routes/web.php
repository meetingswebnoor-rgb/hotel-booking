<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\HotelFilterController;
use App\Controllers\NotificationController;
use App\Controllers\SearchController;
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

    $router->get('/dashboard/data', [DashboardController::class, 'data'], [
        AuthMiddleware::class,
        [RoleMiddleware::class, RoleLevel::READ_ONLY],
        HotelScopeMiddleware::class,
    ], name: 'dashboard.data');

    $router->group(['prefix' => '/bookings', 'middleware' => [AuthMiddleware::class, HotelScopeMiddleware::class]], function (Router $router): void {
        $router->get('/', [BookingController::class, 'index'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'bookings.index');
        $router->get('/data', [BookingController::class, 'data'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'bookings.data');
        $router->get('/create', [BookingController::class, 'create'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.create');
        $router->get('/check-id', [BookingController::class, 'checkId'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.check-id');
        $router->get('/rooms', [BookingController::class, 'roomsForHotel'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.rooms');
        $router->post('/', [BookingController::class, 'store'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.store');
        $router->get('/{id}/edit', [BookingController::class, 'edit'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.edit');
        $router->get('/{id}/detail', [BookingController::class, 'detail'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'bookings.detail');
        $router->post('/{id}', [BookingController::class, 'update'], [[RoleMiddleware::class, RoleLevel::STAFF]], name: 'bookings.update');
        $router->get('/{id}/voucher', [BookingController::class, 'voucher'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'bookings.voucher');
    });

    // App shell endpoints — used by every admin page's topbar/sidebar.
    $router->post('/hotel-filter', [HotelFilterController::class, 'set'], [AuthMiddleware::class], name: 'hotel-filter.set');
    $router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class], name: 'notifications.index');
    $router->get('/notifications/count', [NotificationController::class, 'count'], [AuthMiddleware::class], name: 'notifications.count');
    $router->get('/search', [SearchController::class, 'index'], [AuthMiddleware::class], name: 'search');

    // Further admin/back-office routes register here as those modules land,
    // e.g. gated at a higher level: [[RoleMiddleware::class, RoleLevel::ADMIN]].
};
