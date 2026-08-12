<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\RatePlanService;

/**
 * The standalone, cross-hotel Rate Plans page — same rate plans, same
 * validation, same App\Services\RatePlanService as the hotel hub's
 * Rate Plans tab (App\Controllers\HotelController), just browsable/
 * manageable across every hotel the caller has access to.
 */
final class RatePlanController
{
    public function index(Request $request): Response
    {
        if (!can('rate_plans', 'view')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $hotelIds = $this->effectiveHotelIds($request);
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'rp.hotel_id');

        $sql = "SELECT rp.*, h.name AS hotel_name
                FROM rate_plans rp
                JOIN hotels h ON h.id = rp.hotel_id
                WHERE rp.is_deleted = 0{$scopeSql}
                ORDER BY h.name, rp.plan_name";
        $ratePlans = Database::query($sql, $scopeParams)->fetchAll();

        $html = view('admin/rate-plans/index', [
            'title' => 'Rate Plans',
            'active' => 'rate-plans',
            'pageTitle' => 'Rate Plans',
            'user' => Auth::user(),
            'ratePlans' => $ratePlans,
            'filterHotels' => $this->filterableHotels($request),
            'addableHotels' => $this->allowedHotels(),
            'selectedHotelId' => $request->query('hotel_id'),
            'roomTypes' => RatePlanService::ROOM_TYPES,
            'seasons' => RatePlanService::SEASONS,
            'canCreate' => can('rate_plans', 'create'),
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        $plan = Database::first('rate_plans', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($plan === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->save($request, $plan);
    }

    public function destroy(Request $request): Response
    {
        $plan = Database::first('rate_plans', ['id' => $request->param('id'), 'is_deleted' => 0]);
        $redirectBack = $this->redirectBack($request);

        if ($plan === null) {
            return Response::redirect($redirectBack);
        }

        if (!can('rate_plans', 'delete', $plan['hotel_id'])) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        RatePlanService::delete($plan['id'], Auth::id());
        Session::flash('success', 'Rate plan removed.');

        return Response::redirect($redirectBack);
    }

    private function save(Request $request, ?array $existing): Response
    {
        $redirectBack = $this->redirectBack($request);

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect($redirectBack);
        }

        $hotelId = $existing !== null ? (string) $existing['hotel_id'] : (string) $request->input('hotel_id', '');
        $action = $existing === null ? 'create' : 'edit';

        if ($hotelId === '' || !can('rate_plans', $action, $hotelId)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $errors = RatePlanService::validate($request->all());

        if ($errors !== []) {
            Session::flash('error', 'Please fix the rate plan details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect($redirectBack);
        }

        RatePlanService::save($request->all(), $hotelId, $existing, Auth::roleName());
        Session::flash('success', $existing === null ? 'Rate plan added.' : 'Rate plan updated.');

        return Response::redirect($redirectBack);
    }

    private function redirectBack(Request $request): string
    {
        $hotelId = $request->input('_redirect_hotel_id');
        $base = route('rate-plans.index');

        return is_string($hotelId) && $hotelId !== '' ? $base . '?hotel_id=' . urlencode($hotelId) : $base;
    }

    /**
     * @return array<int, string>|null
     */
    private function effectiveHotelIds(Request $request): ?array
    {
        $scoped = $request->scope('hotel_ids');
        $requested = $request->query('hotel_id');

        if ($requested === null || $requested === '') {
            return $scoped;
        }

        if ($scoped === null) {
            return [$requested];
        }

        return in_array($requested, $scoped, true) ? [$requested] : $scoped;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filterableHotels(Request $request): array
    {
        $scopeIds = $request->scope('hotel_ids');
        $allowed = $this->allowedHotels();

        if ($scopeIds === null) {
            return $allowed;
        }

        return array_values(array_filter($allowed, static fn (array $h): bool => in_array($h['id'], $scopeIds, true)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allowedHotels(): array
    {
        if (Auth::hasGlobalHotelAccess()) {
            return Database::all('hotels', ['is_deleted' => 0], 'id, name', 'name');
        }

        $ids = Auth::hotelIds();

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        return Database::query(
            "SELECT id, name FROM hotels WHERE is_deleted = 0 AND id IN ({$placeholders}) ORDER BY name",
            $ids
        )->fetchAll();
    }
}
