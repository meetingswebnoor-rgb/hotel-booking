<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Backs the topbar notification bell against notification_logs. No
 * feature writes to that table yet, so this will genuinely show an
 * empty state today — the wiring (unread count, mark-as-seen) is
 * real and ready for when bookings/invoices/etc. start logging here.
 */
final class NotificationController
{
    private const SEEN_SESSION_KEY = 'notifications_seen_at';

    /**
     * Lightweight unread count, safe to call on every page load —
     * does NOT mark notifications as seen.
     */
    public function count(Request $request): Response
    {
        $user = Auth::user();
        $seenAt = (string) Session::get(self::SEEN_SESSION_KEY, '1970-01-01 00:00:00');

        $count = (int) Database::query(
            'SELECT COUNT(*) FROM notification_logs WHERE user_id = ? AND is_deleted = 0 AND created_at > ?',
            [$user['id'], $seenAt]
        )->fetchColumn();

        return Response::json(['unread_count' => $count]);
    }

    /**
     * The dropdown's full list. Marks everything as seen as a side
     * effect — the badge should clear once the user has actually
     * looked at the list.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $items = Database::query(
            'SELECT id, subject, event_key, status, created_at FROM notification_logs
             WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 10',
            [$user['id']]
        )->fetchAll();

        Session::set(self::SEEN_SESSION_KEY, date('Y-m-d H:i:s'));

        return Response::json(['items' => $items]);
    }
}
