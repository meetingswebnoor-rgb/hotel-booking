<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use DateTimeImmutable;

/**
 * The analytics dashboard: a static shell (index) plus a JSON
 * aggregate endpoint (data) the frontend polls. Every query in here
 * is scoped by effectiveHotelIds() — the permission-based hotel scope
 * from HotelScopeMiddleware, further narrowed by an optional
 * ?hotel_id= drill-down param (never widened beyond what the user is
 * actually allowed to see).
 *
 * Financial figures (revenue/earnings/commission KPIs, the two
 * revenue-driven charts, and the breakdown table's money columns) are
 * additionally gated behind can('reports', 'view') — front_desk/
 * reception see operational counts but not portfolio financials.
 */
final class DashboardController
{
    public function index(Request $request): Response
    {
        $html = view('admin/dashboard', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'user' => Auth::user(),
            'roleName' => Auth::roleName(),
            'roleLevel' => Auth::level(),
            'canViewReports' => can('reports', 'view'),
        ], 'admin');

        return Response::html($html);
    }

    public function data(Request $request): Response
    {
        $hotelIds = $this->effectiveHotelIds($request);
        $canViewReports = can('reports', 'view');

        $now = new DateTimeImmutable();
        $currentStart = $now->modify('first day of this month');
        $day = (int) $now->format('j');
        $previousStart = $now->modify('first day of last month');
        $previousEnd = $previousStart->modify('+' . ($day - 1) . ' days');

        $currentStartStr = $currentStart->format('Y-m-d');
        $currentEndStr = $now->format('Y-m-d');
        $previousStartStr = $previousStart->format('Y-m-d');
        $previousEndStr = $previousEnd->format('Y-m-d');

        $windowStart = $currentStart->modify('-5 months')->format('Y-m-d');
        $windowEnd = $currentEndStr;

        $bookingsCurrent = $this->scalarForPeriod('COUNT(*)', $currentStartStr, $currentEndStr, $hotelIds);
        $bookingsPrevious = $this->scalarForPeriod('COUNT(*)', $previousStartStr, $previousEndStr, $hotelIds);
        $guestsCurrent = $this->scalarForPeriod('COALESCE(SUM(adults + children), 0)', $currentStartStr, $currentEndStr, $hotelIds);
        $guestsPrevious = $this->scalarForPeriod('COALESCE(SUM(adults + children), 0)', $previousStartStr, $previousEndStr, $hotelIds);
        $otaBookingsCurrent = $this->scalarForPeriod('COUNT(*)', $currentStartStr, $currentEndStr, $hotelIds, "AND source = 'ota'");
        $otaBookingsPrevious = $this->scalarForPeriod('COUNT(*)', $previousStartStr, $previousEndStr, $hotelIds, "AND source = 'ota'");

        $avgCurrent = $bookingsCurrent > 0 ? $guestsCurrent / $bookingsCurrent : 0.0;
        $avgPrevious = $bookingsPrevious > 0 ? $guestsPrevious / $bookingsPrevious : 0.0;

        $hotelsCurrent = $this->hotelCountAsOf($now, $hotelIds);
        $hotelsPrevious = $this->hotelCountAsOf($previousEnd->setTime(23, 59, 59), $hotelIds);

        $kpis = [
            'total_bookings' => array_merge(['value' => (int) $bookingsCurrent], $this->trend($bookingsCurrent, $bookingsPrevious)),
            'total_guests' => array_merge(['value' => (int) $guestsCurrent], $this->trend($guestsCurrent, $guestsPrevious)),
            'avg_guests_per_booking' => array_merge(['value' => round($avgCurrent, 1)], $this->trend($avgCurrent, $avgPrevious)),
            'ota_bookings' => array_merge(['value' => (int) $otaBookingsCurrent], $this->trend($otaBookingsCurrent, $otaBookingsPrevious)),
            'total_hotels' => array_merge(['value' => $hotelsCurrent], $this->trend((float) $hotelsCurrent, (float) $hotelsPrevious)),
        ];

        if ($canViewReports) {
            $kpis['total_revenue'] = $this->kpiBlock('COALESCE(SUM(total_room_rent), 0)', $currentStartStr, $currentEndStr, $previousStartStr, $previousEndStr, $hotelIds);
            $kpis['hotel_earnings'] = $this->kpiBlock('COALESCE(SUM(hotel_earning), 0)', $currentStartStr, $currentEndStr, $previousStartStr, $previousEndStr, $hotelIds);
            $kpis['commissions_taxes'] = $this->kpiBlock('COALESCE(SUM(total_commission_taxes), 0)', $currentStartStr, $currentEndStr, $previousStartStr, $previousEndStr, $hotelIds);
        }

        $charts = [
            'monthly_trend' => $this->monthlyTrend($hotelIds, $now),
            'room_type_distribution' => $this->roomTypeDistribution($hotelIds, $windowStart, $windowEnd),
            'ota_vs_direct' => $this->otaVsDirect($hotelIds, $windowStart, $windowEnd),
        ];

        if ($canViewReports) {
            $charts['revenue_by_ota'] = $this->revenueByOta($hotelIds, $windowStart, $windowEnd);
            $charts['top_hotels'] = $this->topHotelsByEarnings($hotelIds, $windowStart, $windowEnd);
        }

        $windowBookingCount = (int) $this->scalarForPeriod('COUNT(*)', $windowStart, $windowEnd, $hotelIds);

        return Response::json([
            'has_data' => $windowBookingCount > 0,
            'can_view_reports' => $canViewReports,
            'period_label' => $now->format('F Y'),
            'kpis' => $kpis,
            'charts' => $charts,
            'breakdown' => $canViewReports ? $this->perHotelBreakdown($hotelIds, $windowStart, $windowEnd) : [],
            'recent_bookings' => $this->recentBookings($hotelIds, $canViewReports),
            'drill_down_hotel_id' => $request->query('hotel_id'),
        ]);
    }

    /**
     * The permission-based scope, further narrowed (never widened) by
     * an optional ?hotel_id= drill-down from the breakdown table.
     *
     * @return array<int, string>|null
     */
    private function effectiveHotelIds(Request $request): ?array
    {
        $scoped = $request->scope('hotel_ids');
        $drillDown = $request->query('hotel_id');

        if ($drillDown === null || $drillDown === '') {
            return $scoped;
        }

        if ($scoped === null) {
            return [$drillDown];
        }

        return in_array($drillDown, $scoped, true) ? [$drillDown] : $scoped;
    }

    /**
     * @param array<int, string>|null $hotelIds
     */
    private function scalarForPeriod(string $selectExpr, string $start, string $end, ?array $hotelIds, string $extraSql = ''): float
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'hotel_id');
        $sql = "SELECT {$selectExpr} FROM bookings WHERE is_deleted = 0 AND booking_date BETWEEN ? AND ? {$scopeSql} {$extraSql}";

        return (float) Database::query($sql, [$start, $end, ...$scopeParams])->fetchColumn();
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array{value: float, trend: float|null, direction: string}
     */
    private function kpiBlock(string $selectExpr, string $currentStart, string $currentEnd, string $previousStart, string $previousEnd, ?array $hotelIds): array
    {
        $current = $this->scalarForPeriod($selectExpr, $currentStart, $currentEnd, $hotelIds);
        $previous = $this->scalarForPeriod($selectExpr, $previousStart, $previousEnd, $hotelIds);

        return array_merge(['value' => $current], $this->trend($current, $previous));
    }

    /**
     * @return array{trend: float|null, direction: string}
     */
    private function trend(float $current, float $previous): array
    {
        if ($previous === 0.0) {
            return $current > 0.0
                ? ['trend' => null, 'direction' => 'up']
                : ['trend' => 0.0, 'direction' => 'flat'];
        }

        $pct = round((($current - $previous) / $previous) * 100, 1);
        $direction = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');

        return ['trend' => $pct, 'direction' => $direction];
    }

    /**
     * @param array<int, string>|null $hotelIds
     */
    private function hotelCountAsOf(DateTimeImmutable $asOf, ?array $hotelIds): int
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'id');
        $sql = "SELECT COUNT(*) FROM hotels WHERE is_deleted = 0 AND created_at <= ? {$scopeSql}";

        return (int) Database::query($sql, [$asOf->format('Y-m-d H:i:s'), ...$scopeParams])->fetchColumn();
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function monthlyTrend(?array $hotelIds, DateTimeImmutable $now): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'hotel_id');
        $firstOfThisMonth = $now->modify('first day of this month');
        $start = $firstOfThisMonth->modify('-5 months')->format('Y-m-d');
        $end = $now->format('Y-m-d');

        $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m') AS ym, COUNT(*) AS cnt
                FROM bookings
                WHERE is_deleted = 0 AND booking_date BETWEEN ? AND ? {$scopeSql}
                GROUP BY ym";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();
        $byMonth = array_column($rows, 'cnt', 'ym');

        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $firstOfThisMonth->modify("-{$i} months");
            $labels[] = $month->format('M');
            $data[] = (int) ($byMonth[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function revenueByOta(?array $hotelIds, string $start, string $end): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'b.hotel_id');
        $sql = "SELECT COALESCE(o.name, CASE WHEN b.source = 'walkin' THEN 'Walk-in' ELSE 'Direct' END) AS source_name,
                       COALESCE(SUM(b.total_room_rent), 0) AS revenue
                FROM bookings b
                LEFT JOIN otas o ON o.id = b.ota_id
                WHERE b.is_deleted = 0 AND b.booking_date BETWEEN ? AND ? {$scopeSql}
                GROUP BY source_name
                HAVING revenue > 0
                ORDER BY revenue DESC";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();

        return [
            'labels' => array_column($rows, 'source_name'),
            'data' => array_map(static fn (array $r): float => (float) $r['revenue'], $rows),
        ];
    }

    /**
     * bookings.rooms is a JSON array with no portable server-side
     * GROUP BY (MariaDB 10.4 here predates JSON_TABLE), so this
     * decodes it in PHP instead.
     *
     * @param array<int, string>|null $hotelIds
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function roomTypeDistribution(?array $hotelIds, string $start, string $end): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'hotel_id');
        $sql = "SELECT rooms FROM bookings WHERE is_deleted = 0 AND booking_date BETWEEN ? AND ? {$scopeSql}";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();

        $counts = [];

        foreach ($rows as $row) {
            $lines = json_decode((string) $row['rooms'], true) ?: [];

            foreach ($lines as $line) {
                $type = $line['room_type'] ?? 'Unknown';
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }

        arsort($counts);

        return ['labels' => array_keys($counts), 'data' => array_values($counts)];
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function topHotelsByEarnings(?array $hotelIds, string $start, string $end): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'b.hotel_id');
        $sql = "SELECT h.name, COALESCE(SUM(b.hotel_earning), 0) AS earnings
                FROM bookings b JOIN hotels h ON h.id = b.hotel_id
                WHERE b.is_deleted = 0 AND b.booking_date BETWEEN ? AND ? {$scopeSql}
                GROUP BY b.hotel_id, h.name
                ORDER BY earnings DESC
                LIMIT 5";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();

        return [
            'labels' => array_column($rows, 'name'),
            'data' => array_map(static fn (array $r): float => (float) $r['earnings'], $rows),
        ];
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    private function otaVsDirect(?array $hotelIds, string $start, string $end): array
    {
        // source, not ota_id nullability — Direct Booking/Walk-in are
        // themselves rows in `otas` (0% commission), so every booking
        // has a non-null ota_id regardless of channel.
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'hotel_id');
        $sql = "SELECT CASE WHEN source = 'ota' THEN 'OTA' ELSE 'Direct' END AS bucket, COUNT(*) AS cnt
                FROM bookings
                WHERE is_deleted = 0 AND booking_date BETWEEN ? AND ? {$scopeSql}
                GROUP BY bucket";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();
        $byBucket = array_column($rows, 'cnt', 'bucket');

        return [
            'labels' => ['OTA', 'Direct'],
            'data' => [(int) ($byBucket['OTA'] ?? 0), (int) ($byBucket['Direct'] ?? 0)],
        ];
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array<int, array<string, mixed>>
     */
    private function perHotelBreakdown(?array $hotelIds, string $start, string $end): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'b.hotel_id');
        $sql = "SELECT b.hotel_id, h.name AS hotel_name, COUNT(*) AS bookings,
                       COALESCE(SUM(b.total_room_rent), 0) AS revenue,
                       COALESCE(SUM(b.hotel_earning), 0) AS earnings,
                       COALESCE(SUM(b.total_commission_taxes), 0) AS commission_tax
                FROM bookings b JOIN hotels h ON h.id = b.hotel_id
                WHERE b.is_deleted = 0 AND b.booking_date BETWEEN ? AND ? {$scopeSql}
                GROUP BY b.hotel_id, h.name
                ORDER BY revenue DESC";
        $rows = Database::query($sql, [$start, $end, ...$scopeParams])->fetchAll();

        return array_map(static fn (array $row): array => [
            'hotel_id' => $row['hotel_id'],
            'hotel_name' => $row['hotel_name'],
            'bookings' => (int) $row['bookings'],
            'revenue' => (float) $row['revenue'],
            'earnings' => (float) $row['earnings'],
            'commission_tax' => (float) $row['commission_tax'],
        ], $rows);
    }

    /**
     * @param array<int, string>|null $hotelIds
     * @return array<int, array<string, mixed>>
     */
    private function recentBookings(?array $hotelIds, bool $includeAmount): array
    {
        [$scopeSql, $scopeParams] = Database::scopeCondition($hotelIds, 'b.hotel_id');
        $sql = "SELECT b.booking_id, b.guest_name, b.checkin_date, b.status, b.total_room_rent, h.name AS hotel_name
                FROM bookings b JOIN hotels h ON h.id = b.hotel_id
                WHERE b.is_deleted = 0 {$scopeSql}
                ORDER BY b.created_at DESC
                LIMIT 6";
        $rows = Database::query($sql, $scopeParams)->fetchAll();

        return array_map(static function (array $row) use ($includeAmount): array {
            $item = [
                'booking_id' => $row['booking_id'],
                'guest_name' => $row['guest_name'],
                'hotel_name' => $row['hotel_name'],
                'checkin_date' => $row['checkin_date'],
                'status' => $row['status'],
            ];

            if ($includeAmount) {
                $item['total_room_rent'] = (float) $row['total_room_rent'];
            }

            return $item;
        }, $rows);
    }
}
