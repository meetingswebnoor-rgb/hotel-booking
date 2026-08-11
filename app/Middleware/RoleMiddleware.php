<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * Route usage: ['middleware' => [[RoleMiddleware::class, 'super_admin', 'hotel_admin']]]
 */
final class RoleMiddleware implements MiddlewareInterface
{
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check() || !Auth::hasRole(...$this->roles)) {
            if ($request->isAjax()) {
                return Response::json(['message' => 'Forbidden.'], 403);
            }

            return Response::html(view('errors/403', [], 'public'), 403);
        }

        return $next($request);
    }
}
