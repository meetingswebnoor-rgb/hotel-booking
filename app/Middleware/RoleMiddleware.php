<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * Coarse route-level gate on role level. Route usage:
 *   [[RoleMiddleware::class, RoleLevel::ADMIN]]
 *
 * For fine-grained, module-specific checks use App\Core\Permission
 * (the can() helper) instead — level alone can't distinguish e.g.
 * Revenue Manager from OTA Manager, since both are level 2.
 */
final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly int $minLevel)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::hasMinLevel($this->minLevel)) {
            if ($request->isAjax()) {
                return Response::json(['message' => 'Forbidden.'], 403);
            }

            return Response::html(view('errors/403', [], 'public'), 403);
        }

        return $next($request);
    }
}
