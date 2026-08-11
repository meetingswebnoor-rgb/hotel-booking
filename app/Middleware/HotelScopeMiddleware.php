<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * Injects the caller's allowed hotel IDs into the request so every
 * downstream query can filter by them. $request->scope('hotel_ids')
 * is `null` for unrestricted (Admin/Super Admin) or an array
 * (possibly empty, meaning "no hotels assigned") for everyone else.
 */
final class HotelScopeMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $request->setScope('hotel_ids', Auth::hasGlobalHotelAccess() ? null : Auth::hotelIds());

        return $next($request);
    }
}
