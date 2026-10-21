<?php
// app/models/ReportModel.php
// MODEL for the admin "Reports" page (?page=reports): how the business did between two dates.
// Bookings are counted by the day they were made; money (bill, commission) by the day the work was finished.
// bill = final_total when the office changed it, else total.

class ReportModel
{
    const BILL = 'COALESCE(b.final_total, b.total)';

    // ---- The numbers on top: bookings, finished jobs, money, ratings, complaints, new customers
    public static function summary($from, $to)
    {
        $db = Database::get();
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $st = $db->prepare("SELECT COUNT(*) AS made,
                SUM(b.status = 'pending') AS pending, SUM(b.status = 'ongoing') AS ongoing,
                SUM(b.status = 'cancelled') AS cancelled, SUM(b.status = 'cancelled' AND b.cancelled_by = 'user') AS cancelled_user
            FROM bookings b WHERE b.created_at BETWEEN ? AND ?");
        $st->execute($range);
        $made = $st->fetch();

        $st = $db->prepare('SELECT COUNT(*) AS done, COALESCE(SUM(' . self::BILL . '), 0) AS bill, COALESCE(SUM(b.commission), 0) AS commission,
                COALESCE(SUM(b.discount), 0) AS discount, AVG(b.rating) AS rating, COUNT(b.rating) AS rated,
                COUNT(b.complaint) AS complaints, SUM(b.complaint_status = \'open\') AS open_complaints
            FROM bookings b WHERE b.status = \'completed\' AND b.completed_at BETWEEN ? AND ?');
        $st->execute($range);
        $done = $st->fetch();

        $st = $db->prepare('SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ? AND deleted_at IS NULL');
        $st->execute($range);
        $newUsers = (int)$st->fetchColumn();

        $st = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM wallet_tx WHERE kind = 'topup' AND status = 'successful' AND created_at BETWEEN ? AND ?");
        $st->execute($range);
        $topups = (int)$st->fetchColumn();

        return array_map(function ($v) { return $v === null ? 0 : $v; }, $made + $done) + ['new_users' => $newUsers, 'topups' => $topups];
    }

    // ---- Finished jobs grouped by service (category), most money first
    public static function byService($from, $to, $lang)
    {
        $name = $lang === 'en' ? 's.name_en' : 's.name_bn';
        return self::grouped("SELECT $name AS name, COUNT(*) AS jobs, SUM(" . self::BILL . ") AS bill, SUM(b.commission) AS commission, AVG(b.rating) AS rating
            FROM bookings b JOIN sub_services ss ON ss.id = b.sub_service_id JOIN services s ON s.id = ss.service_id
            WHERE b.status = 'completed' AND b.completed_at BETWEEN ? AND ? GROUP BY s.id ORDER BY bill DESC", $from, $to);
    }

    // ---- Bookings made, grouped by area (where the demand is), most first
    public static function byArea($from, $to)
    {
        return self::grouped("SELECT b.area AS name, COUNT(*) AS made, SUM(b.status = 'completed') AS jobs, SUM(b.status = 'cancelled') AS cancelled
            FROM bookings b WHERE b.created_at BETWEEN ? AND ? GROUP BY b.area ORDER BY made DESC, name LIMIT 10", $from, $to);
    }

    // ---- Providers by finished jobs, with their stars and complaints
    public static function byProvider($from, $to)
    {
        return self::grouped('SELECT p.id, p.name, COUNT(*) AS jobs, SUM(' . self::BILL . ') AS bill, AVG(b.rating) AS rating, COUNT(b.complaint) AS complaints
            FROM bookings b JOIN providers p ON p.id = b.provider_id
            WHERE b.status = \'completed\' AND b.completed_at BETWEEN ? AND ? GROUP BY p.id ORDER BY jobs DESC, bill DESC LIMIT 10', $from, $to);
    }

    // ---- Bookings made per day (small bar chart). Every day in the range, 0 when none (max 92 days, else per month).
    public static function perDay($from, $to)
    {
        $days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
        $byMonth = $days > 92;
        $fmt = $byMonth ? '%Y-%m' : '%Y-%m-%d';
        $rows = self::grouped("SELECT DATE_FORMAT(b.created_at, '$fmt') AS d, COUNT(*) AS n FROM bookings b
            WHERE b.created_at BETWEEN ? AND ? GROUP BY d", $from, $to);
        $have = array_column($rows, 'n', 'd');
        $out = [];
        for ($t = strtotime($from); $t <= strtotime($to); $t = $byMonth ? strtotime('first day of next month', $t) : $t + 86400) {
            $key = date($byMonth ? 'Y-m' : 'Y-m-d', $t);
            $out[$key] = (int)($have[$key] ?? 0);
        }
        return [$out, $byMonth];
    }

    private static function grouped($sql, $from, $to)
    {
        $st = Database::get()->prepare($sql);
        $st->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        return $st->fetchAll();
    }
}
