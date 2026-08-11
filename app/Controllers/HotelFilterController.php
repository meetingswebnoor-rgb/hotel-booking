<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Sets the topbar's global hotel filter — a session-persisted choice
 * that HotelScopeMiddleware layers on top of the user's actual
 * permission-based hotel scope (it can only narrow, never widen it).
 */
final class HotelFilterController
{
    private const SESSION_KEY = 'selected_hotel_id';

    public function set(Request $request): Response
    {
        if (!Csrf::verify($request->input('_csrf'))) {
            return $this->back($request);
        }

        $hotelId = trim((string) $request->input('hotel_id', ''));

        if ($hotelId === '') {
            Session::remove(self::SESSION_KEY);

            return $this->back($request);
        }

        if (Auth::hasGlobalHotelAccess() || in_array($hotelId, Auth::hotelIds(), true)) {
            Session::set(self::SESSION_KEY, $hotelId);
        }

        return $this->back($request);
    }

    private function back(Request $request): Response
    {
        $referer = $request->header('Referer');

        return Response::redirect(is_string($referer) && $referer !== '' ? $referer : route('dashboard'));
    }
}
