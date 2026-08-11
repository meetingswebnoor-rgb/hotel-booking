<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Injects the caller's allowed hotel IDs into the request so every
 * downstream query can filter by them, narrowed further by whatever
 * the topbar hotel filter has selected for this session.
 *
 * $request->scope('hotel_ids') is `null` for unrestricted (Admin/Super
 * Admin, with no filter selected) or an array (possibly empty, meaning
 * "no hotels in scope") for everyone else / once a filter is picked.
 * $request->scope('selected_hotel_id') is the raw session selection,
 * for the topbar to know what's currently active.
 */
final class HotelScopeMiddleware implements MiddlewareInterface
{
    private const SESSION_KEY = 'selected_hotel_id';

    public function handle(Request $request, callable $next): Response
    {
        $allowed = Auth::hasGlobalHotelAccess() ? null : Auth::hotelIds();
        $selected = Session::get(self::SESSION_KEY);

        if ($selected !== null) {
            $withinAllowed = $allowed === null || in_array($selected, $allowed, true);

            if ($withinAllowed) {
                $allowed = [$selected];
            } else {
                // Session references a hotel the user is no longer
                // assigned to (e.g. reassigned since selecting it) —
                // drop the stale selection rather than silently
                // narrowing to nothing.
                Session::remove(self::SESSION_KEY);
                $selected = null;
            }
        }

        $request->setScope('hotel_ids', $allowed);
        $request->setScope('selected_hotel_id', $selected);

        return $next($request);
    }
}
