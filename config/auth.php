<?php

declare(strict_types=1);

return [
    // Role assigned to a new user when none is specified.
    'default_role' => 'reception',

    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),
    'remember_days' => (int) env('AUTH_REMEMBER_DAYS', 30),
];
