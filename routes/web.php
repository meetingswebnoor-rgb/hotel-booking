<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CommissionInvoiceController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\HotelController;
use App\Controllers\HotelFilterController;
use App\Controllers\NotificationController;
use App\Controllers\OtaController;
use App\Controllers\PublicHotelController;
use App\Controllers\RatePlanController;
use App\Controllers\RoomController;
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
    $router->get('/privacy', [HomeController::class, 'privacy'], name: 'privacy');
    $router->get('/terms', [HomeController::class, 'terms'], name: 'terms');

    // The public hotel directory can't live at /hotels — that path is
    // already the auth-scoped admin Hotel Management list
    // (App\Controllers\HotelController) registered below, and this
    // router has no way to disambiguate two GET routes on the same
    // path. /explore is the public-facing equivalent.
    $router->get('/explore', [PublicHotelController::class, 'index'], name: 'public.hotels.index');
    $router->get('/hotel/{slug}', [PublicHotelController::class, 'show'], name: 'public.hotels.show');

    $router->get('/login', [AuthController::class, 'showLogin'], name: 'login');
    $router->post('/login', [AuthController::class, 'login'], name: 'login.submit');
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class], name: 'logout');

    // The landing page's Live Demo section — see
    // App\Controllers\AuthController::demoLogin() for the DEMO_MODE gate.
    $router->post('/demo-login', [AuthController::class, 'demoLogin'], name: 'demo-login');

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

    // Static-looking sub-paths (create, etc.) must be registered before
    // the dynamic /{id} route below — the router matches in order and
    // {id} greedily matches any single segment, "create" included.
    $router->group(['prefix' => '/hotels', 'middleware' => [AuthMiddleware::class, HotelScopeMiddleware::class]], function (Router $router): void {
        $router->get('/', [HotelController::class, 'index'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'hotels.index');
        $router->get('/create', [HotelController::class, 'create'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.create');
        $router->post('/', [HotelController::class, 'store'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.store');
        $router->get('/{id}', [HotelController::class, 'show'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'hotels.show');
        $router->post('/{id}', [HotelController::class, 'update'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.update');
        $router->post('/{id}/delete', [HotelController::class, 'destroy'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.destroy');

        $router->post('/{id}/rooms', [HotelController::class, 'storeRoom'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.rooms.store');
        $router->post('/{id}/rooms/{roomId}', [HotelController::class, 'updateRoom'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.rooms.update');
        $router->post('/{id}/rooms/{roomId}/delete', [HotelController::class, 'destroyRoom'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.rooms.destroy');

        // MANAGER (not HOTEL_MANAGER) on store/update — config/permissions.php
        // grants revenue_manager (level 2) rate_plans.edit, so the route-level
        // gate must not block them before can() ever gets a say. No role
        // below hotel_manager has rate_plans.delete, so destroy stays higher.
        $router->post('/{id}/rate-plans', [HotelController::class, 'storeRatePlan'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'hotels.rate-plans.store');
        $router->post('/{id}/rate-plans/{planId}', [HotelController::class, 'updateRatePlan'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'hotels.rate-plans.update');
        $router->post('/{id}/rate-plans/{planId}/delete', [HotelController::class, 'destroyRatePlan'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'hotels.rate-plans.destroy');
    });

    // Standalone, cross-hotel Rooms and Rate Plans pages — same
    // App\Services\RoomService / RatePlanService (and so identical
    // validation/behavior) as the hotel hub's tabs above, just not
    // scoped to one hotel at a time. No dynamic GET /{id} route exists
    // for either (edit happens via modal, not a page), so there's no
    // static-vs-dynamic ordering concern like /hotels has.
    $router->group(['prefix' => '/rooms', 'middleware' => [AuthMiddleware::class, HotelScopeMiddleware::class]], function (Router $router): void {
        $router->get('/', [RoomController::class, 'index'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'rooms.index');
        $router->post('/', [RoomController::class, 'store'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'rooms.store');
        $router->post('/{id}', [RoomController::class, 'update'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'rooms.update');
        $router->post('/{id}/delete', [RoomController::class, 'destroy'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'rooms.destroy');
    });

    $router->group(['prefix' => '/rate-plans', 'middleware' => [AuthMiddleware::class, HotelScopeMiddleware::class]], function (Router $router): void {
        $router->get('/', [RatePlanController::class, 'index'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'rate-plans.index');
        $router->post('/', [RatePlanController::class, 'store'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'rate-plans.store');
        $router->post('/{id}', [RatePlanController::class, 'update'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'rate-plans.update');
        $router->post('/{id}/delete', [RatePlanController::class, 'destroy'], [[RoleMiddleware::class, RoleLevel::HOTEL_MANAGER]], name: 'rate-plans.destroy');
    });

    // OTAs are global partner records, not hotel-scoped, so this group
    // carries no HotelScopeMiddleware — unlike Rooms/Rate Plans/Bookings
    // above, there's no per-hotel data to narrow here. Route-level gates
    // mirror config/permissions.php's lowest-granted role per action:
    // MANAGER (ota_manager, level 2) has create/edit, only ADMIN has
    // delete — can() still does the real per-request enforcement.
    //
    // /reviews and /reviews/{id}/delete must be registered before /{id}
    // and /{id}/delete below — same static-before-dynamic ordering rule
    // as the /hotels group above ({id} greedily matches "reviews" too).
    $router->group(['prefix' => '/otas', 'middleware' => [AuthMiddleware::class]], function (Router $router): void {
        $router->get('/', [OtaController::class, 'index'], [[RoleMiddleware::class, RoleLevel::READ_ONLY]], name: 'otas.index');
        $router->post('/', [OtaController::class, 'store'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'otas.store');
        $router->post('/reviews', [OtaController::class, 'storeReview'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'otas.reviews.store');
        $router->post('/reviews/{id}/delete', [OtaController::class, 'destroyReview'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'otas.reviews.destroy');
        $router->post('/{id}', [OtaController::class, 'update'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'otas.update');
        $router->post('/{id}/delete', [OtaController::class, 'destroy'], [[RoleMiddleware::class, RoleLevel::ADMIN]], name: 'otas.destroy');
    });

    // Commission invoices (Hotezo billing a hotel for Hotezo's own
    // commission) are gated narrower than the rest of the app's
    // 'invoices' permission module — Super Admin, Admin, or Accounts
    // only, checked directly in the controller (config/permissions.php's
    // 'invoices' entry is shared with hotel_manager/front_desk for
    // guest/service invoicing, which is a different concern). MANAGER
    // is the route-level floor since `accounts` is level 2; the
    // controller's canManage() does the real, role-name-specific check.
    // /create and /preview must be registered before /{id} — same
    // static-before-dynamic ordering rule as every other group here.
    $router->group(['prefix' => '/commission-invoices', 'middleware' => [AuthMiddleware::class, HotelScopeMiddleware::class]], function (Router $router): void {
        $router->get('/', [CommissionInvoiceController::class, 'index'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'commission-invoices.index');
        $router->get('/create', [CommissionInvoiceController::class, 'create'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'commission-invoices.create');
        $router->get('/preview', [CommissionInvoiceController::class, 'preview'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'commission-invoices.preview');
        $router->post('/', [CommissionInvoiceController::class, 'store'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'commission-invoices.store');
        $router->get('/{id}', [CommissionInvoiceController::class, 'show'], [[RoleMiddleware::class, RoleLevel::MANAGER]], name: 'commission-invoices.show');
    });

    // App shell endpoints — used by every admin page's topbar/sidebar.
    $router->post('/hotel-filter', [HotelFilterController::class, 'set'], [AuthMiddleware::class], name: 'hotel-filter.set');
    $router->get('/notifications', [NotificationController::class, 'index'], [AuthMiddleware::class], name: 'notifications.index');
    $router->get('/notifications/count', [NotificationController::class, 'count'], [AuthMiddleware::class], name: 'notifications.count');
    $router->get('/search', [SearchController::class, 'index'], [AuthMiddleware::class], name: 'search');

    // Further admin/back-office routes register here as those modules land,
    // e.g. gated at a higher level: [[RoleMiddleware::class, RoleLevel::ADMIN]].
};
