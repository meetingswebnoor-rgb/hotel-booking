<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * Restricts non-super-admin users to their assigned hotel. Sets
 * $request->scope('hotel_id') for controllers/models to filter by.
 */
final class HotelScopeMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::hasRole('super_admin')) {
            $request->setScope('hotel_id', Auth::hotelId());
        }

        return $next($request);
    }
}
