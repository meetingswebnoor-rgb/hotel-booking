<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Sample protected route demonstrating the auth stack: requires login
 * (AuthMiddleware), a minimum role level (RoleMiddleware), and shows
 * only the hotels HotelScopeMiddleware allowed for this user.
 */
final class DashboardController
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $hotels = $this->scopedHotels($request->scope('hotel_ids'));

        $html = view('admin/dashboard', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'user' => $user,
            'roleName' => Auth::roleName(),
            'roleLevel' => Auth::level(),
            'hotels' => $hotels,
            'hasGlobalHotelAccess' => Auth::hasGlobalHotelAccess(),
        ], 'admin');

        return Response::html($html);
    }

    /**
     * @param array<int, string>|null $hotelIds null = unrestricted (all hotels)
     * @return array<int, array<string, mixed>>
     */
    private function scopedHotels(?array $hotelIds): array
    {
        if ($hotelIds === null) {
            return Database::all('hotels', ['is_deleted' => 0], '*', 'name');
        }

        if ($hotelIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($hotelIds), '?'));
        $sql = "SELECT * FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name";

        return Database::query($sql, $hotelIds)->fetchAll();
    }
}
