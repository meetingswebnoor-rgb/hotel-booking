<?php

declare(strict_types=1);

/**
 * Role -> module -> allowed actions. Consulted by App\Core\Permission
 * only for roles below RoleLevel::SUPER_ADMIN (Super Admin bypasses
 * this matrix entirely). Per-user/per-hotel overrides in
 * user_permissions take precedence over these defaults.
 *
 * Actions are plain CRUD verbs: view, create, edit, delete.
 */
return [
    'admin' => [
        'users' => ['view', 'create', 'edit', 'delete'],
        'hotels' => ['view', 'create', 'edit', 'delete'],
        'otas' => ['view', 'create', 'edit', 'delete'],
        'rooms' => ['view', 'create', 'edit', 'delete'],
        'rate_plans' => ['view', 'create', 'edit', 'delete'],
        'bookings' => ['view', 'create', 'edit', 'delete'],
        'invoices' => ['view', 'create', 'edit'],
        'settlements' => ['view', 'create', 'edit'],
        'reports' => ['view'],
        'settings' => ['view', 'edit'],
    ],

    'hotel_manager' => [
        'hotels' => ['view', 'edit'],
        'rooms' => ['view', 'create', 'edit', 'delete'],
        'rate_plans' => ['view', 'create', 'edit', 'delete'],
        'bookings' => ['view', 'create', 'edit', 'delete'],
        'invoices' => ['view', 'create', 'edit'],
        'settlements' => ['view'],
        'reports' => ['view'],
        'users' => ['view'],
    ],

    'revenue_manager' => [
        'reports' => ['view'],
        'rate_plans' => ['view', 'edit'],
        'bookings' => ['view'],
        'settlements' => ['view'],
    ],

    'ota_manager' => [
        'otas' => ['view', 'create', 'edit'],
        'bookings' => ['view'],
        'reports' => ['view'],
    ],

    'reservation_manager' => [
        'bookings' => ['view', 'create', 'edit'],
        'invoices' => ['view', 'create'],
    ],

    'accounts' => [
        'settlements' => ['view', 'create', 'edit'],
        'invoices' => ['view', 'create', 'edit'],
        'reports' => ['view'],
    ],

    'front_desk' => [
        'bookings' => ['view', 'create', 'edit'],
        'invoices' => ['view', 'create'],
    ],

    'reception' => [
        'bookings' => ['view', 'create'],
    ],

    'read_only' => [
        'hotels' => ['view'],
        'rooms' => ['view'],
        'rate_plans' => ['view'],
        'otas' => ['view'],
        'bookings' => ['view'],
        'invoices' => ['view'],
        'settlements' => ['view'],
        'reports' => ['view'],
    ],
];
