<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\OtaService;

/**
 * OTA partner management — a brand-colored card grid (name, commission,
 * settlement rules, status, and per-OTA custom payment-status labels
 * that the booking form merges into its dropdown once that OTA is
 * selected) plus a Reviews section underneath. OTAs are global, not
 * hotel-scoped, so unlike Rooms/Rate Plans/Bookings this module carries
 * no HotelScopeMiddleware and no per-hotel filter.
 */
final class OtaController
{
    public function index(Request $request): Response
    {
        if (!can('otas', 'view')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM ota_reviews r WHERE r.ota_id = o.id AND r.is_deleted = 0) AS review_count,
                       (SELECT AVG(r.rating) FROM ota_reviews r WHERE r.ota_id = o.id AND r.is_deleted = 0) AS avg_rating
                FROM otas o
                WHERE o.is_deleted = 0
                ORDER BY o.name";
        $otas = Database::query($sql)->fetchAll();

        $selectedOtaId = $request->query('ota_id');
        $reviewWhere = 'r.is_deleted = 0';
        $reviewParams = [];

        if (is_string($selectedOtaId) && $selectedOtaId !== '') {
            $reviewWhere .= ' AND r.ota_id = ?';
            $reviewParams[] = $selectedOtaId;
        }

        $reviews = Database::query(
            "SELECT r.*, o.name AS ota_name, o.brand_color AS ota_color
             FROM ota_reviews r
             JOIN otas o ON o.id = r.ota_id
             WHERE {$reviewWhere}
             ORDER BY r.created_at DESC
             LIMIT 100",
            $reviewParams
        )->fetchAll();

        $html = view('admin/otas/index', [
            'title' => 'OTAs',
            'active' => 'otas',
            'pageTitle' => 'OTAs',
            'user' => Auth::user(),
            'otas' => $otas,
            'reviews' => $reviews,
            'selectedOtaId' => $selectedOtaId,
            'statusOptions' => OtaService::STATUSES,
            'canCreate' => can('otas', 'create'),
            'canManage' => can('otas', 'edit'),
            'canDelete' => can('otas', 'delete'),
        ], 'admin');

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        $ota = Database::first('otas', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($ota === null) {
            return Response::html(view('errors/404', [], 'public'), 404);
        }

        return $this->save($request, $ota);
    }

    public function destroy(Request $request): Response
    {
        if (!can('otas', 'delete')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        $ota = Database::first('otas', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($ota === null) {
            return Response::redirect(route('otas.index'));
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('otas.index'));
        }

        OtaService::delete($ota['id'], Auth::id());
        Session::flash('success', 'OTA removed.');

        return Response::redirect(route('otas.index'));
    }

    public function storeReview(Request $request): Response
    {
        if (!can('otas', 'edit')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('otas.index'));
        }

        $otaId = (string) $request->input('ota_id', '');
        $ota = $otaId !== '' ? Database::first('otas', ['id' => $otaId, 'is_deleted' => 0]) : null;

        if ($ota === null) {
            Session::flash('error', 'Select a valid OTA for this review.');

            return Response::redirect(route('otas.index'));
        }

        $errors = OtaService::validateReview($request->all());

        if ($errors !== []) {
            Session::flash('error', 'Please fix the review details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect(route('otas.index'));
        }

        OtaService::saveReview($otaId, $request->all(), Auth::roleName());
        Session::flash('success', 'Review added.');

        return Response::redirect(route('otas.index'));
    }

    public function destroyReview(Request $request): Response
    {
        if (!can('otas', 'edit')) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('otas.index'));
        }

        $review = Database::first('ota_reviews', ['id' => $request->param('id'), 'is_deleted' => 0]);

        if ($review !== null) {
            OtaService::deleteReview($review['id'], Auth::id());
            Session::flash('success', 'Review removed.');
        }

        return Response::redirect(route('otas.index'));
    }

    private function save(Request $request, ?array $existing): Response
    {
        $action = $existing === null ? 'create' : 'edit';

        if (!can('otas', $action)) {
            return Response::html(view('errors/403', [], 'public'), 403);
        }

        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('otas.index'));
        }

        $errors = OtaService::validate($request->all(), $existing['id'] ?? null);

        if ($errors !== []) {
            Session::flash('error', 'Please fix the OTA details — ' . implode(' ', array_merge(...array_values($errors))));

            return Response::redirect(route('otas.index'));
        }

        $customStatuses = OtaService::parseCustomPaymentStatuses($request->input('custom_payment_statuses'));
        OtaService::save($request->all(), $customStatuses, $existing, Auth::roleName());
        Session::flash('success', $existing === null ? 'OTA added.' : 'OTA updated.');

        return Response::redirect(route('otas.index'));
    }
}
