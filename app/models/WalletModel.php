<?php
// app/models/WalletModel.php
// MODEL for the user's wallet (wallet_tx) and coupons.
//   Balance = SUM(amount) of 'successful' rows. A top-up is 'pending' until the office checks the transaction ID.
//   Coupons: each one once per user. valid_days = days after the account was opened; expires_on = last day for everyone.
// Used by the user's wallet / coupon pages, the booking form (BookingModel) and the office top-up + coupon pages.

class WalletModel
{
    const METHODS   = ['bkash', 'nagad', 'rocket'];   // top-up ways (shown only when the admin filled the number)
    const MIN_TOPUP = 50;
    const MAX_TOPUP = 25000;

    // ---- Small database helpers

    private static function db()
    {
        return Database::get();
    }

    private static function all($sql, array $args = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    private static function one($sql, array $args = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($args);
        return $st->fetch() ?: null;
    }

    private static function run($sql, array $args = [])
    {
        self::db()->prepare($sql)->execute($args);
    }

    // Value of a "SELECT ... AS n" query as a number
    private static function number($sql, array $args = [])
    {
        return (int)self::one($sql, $args)['n'];
    }

    // Text for a LIKE search: % and _ typed by the user are searched as normal letters
    private static function likePattern($q)
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    }

    // ------------------------------------------------------------ WALLET

    public static function balance($userId)
    {
        return self::number('SELECT COALESCE(SUM(amount), 0) AS n FROM wallet_tx WHERE user_id = ? AND status = "successful"', [$userId]);
    }

    // Money in (+) or out (-) that needs no check: booking payment, refund
    public static function addTx($userId, $kind, $amount, $bookingId = null)
    {
        self::run('INSERT INTO wallet_tx (user_id, kind, amount, status, booking_id) VALUES (?, ?, ?, "successful", ?)',
            [$userId, $kind, $amount, $bookingId]);
    }

    // A user's transactions, newest first, with the booked service name (for "AC Service booking")
    public static function history($userId, $limit = 100)
    {
        return self::all(
            'SELECT w.*, x.name_bn, x.name_en, s.icon AS service_icon, s.color AS service_color
             FROM wallet_tx w
             LEFT JOIN bookings b ON b.id = w.booking_id
             LEFT JOIN sub_services x ON x.id = b.sub_service_id
             LEFT JOIN services s ON s.id = x.service_id
             WHERE w.user_id = ? ORDER BY w.created_at DESC, w.id DESC LIMIT ' . (int)$limit, [$userId]);
    }

    // User sent money and typed the transaction ID -> waits for the office.
    // Throws PDOException 23000 if the ID was used before (trx_id is unique).
    public static function requestTopup($userId, $method, $amount, $trxId)
    {
        self::run('INSERT INTO wallet_tx (user_id, kind, amount, status, method, trx_id) VALUES (?, "topup", ?, "pending", ?, ?)',
            [$userId, $amount, $method, $trxId]);
    }

    public static function pendingTopupsOf($userId)
    {
        return self::number('SELECT COUNT(*) AS n FROM wallet_tx WHERE user_id = ? AND kind = "topup" AND status = "pending"', [$userId]);
    }

    // ------------------------------------------------------------ OFFICE: top-up requests

    // Status tab + search (transaction ID, user name / mobile) + pages of $per. Returns [rows, total, page, pages].
    public static function topups($status, $q, $page, $per = 25)
    {
        $where = 'WHERE w.kind = "topup"';
        $args = [];
        if (in_array($status, ['pending', 'successful', 'rejected'], true)) {
            $where .= ' AND w.status = ?';
            $args[] = $status;
        }
        $q = trim((string)$q);
        if ($q !== '') {
            $like = self::likePattern($q);
            $where .= ' AND (w.trx_id LIKE ? OR u.name LIKE ? OR u.mobile LIKE ?)';
            array_push($args, $like, $like, $like);
        }

        // count first, so a page number past the end can be pulled back to the last page
        $count = self::db()->prepare('SELECT COUNT(*) FROM wallet_tx w JOIN users u ON u.id = w.user_id ' . $where);
        $count->execute($args);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page = min(max(1, (int)$page), $pages);

        $order = $status === 'pending' ? 'w.id ASC' : 'w.id DESC';       // waiting: oldest first (first come, first checked)
        $rows = self::all('SELECT w.*, u.name AS user_name, u.mobile AS user_mobile, s.name AS checked_name
                           FROM wallet_tx w JOIN users u ON u.id = w.user_id LEFT JOIN staff s ON s.id = w.checked_by
                           ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
        return [$rows, $total, $page, $pages];
    }

    public static function topup($id)
    {
        return self::one('SELECT * FROM wallet_tx WHERE id = ? AND kind = "topup"', [$id]);
    }

    // Approve / reject a waiting top-up (only once: a checked one can't change). Returns true when it changed now.
    public static function checkTopup($id, $ok, $staffId)
    {
        $st = self::db()->prepare('UPDATE wallet_tx SET status = ?, checked_by = ?, checked_at = NOW() WHERE id = ? AND kind = "topup" AND status = "pending"');
        $st->execute([$ok ? 'successful' : 'rejected', $staffId, $id]);
        return $st->rowCount() === 1;
    }

    // All waiting top-ups (red number in the office menu)
    public static function pendingTopups()
    {
        return self::number('SELECT COUNT(*) AS n FROM wallet_tx WHERE kind = "topup" AND status = "pending"');
    }

    // ------------------------------------------------------------ COUPONS (office)

    // Admin list with who made each one and how many times it was used
    public static function coupons()
    {
        return self::all('SELECT c.*, s.name AS created_name, (SELECT COUNT(*) FROM coupon_uses u WHERE u.coupon_id = c.id) AS uses
                          FROM coupons c LEFT JOIN staff s ON s.id = c.created_by ORDER BY c.id ASC');
    }

    public static function coupon($id)
    {
        return self::one('SELECT * FROM coupons WHERE id = ?', [$id]);
    }

    // Codes are saved in capitals, so the user can type them in any case
    public static function couponByCode($code)
    {
        return self::one('SELECT * FROM coupons WHERE code = ?', [strtoupper(trim((string)$code))]);
    }

    // Add ($id = 0, created_by = $by) or edit
    public static function saveCoupon($id, array $d, $by)
    {
        $values = [$d['code'], $d['title_bn'], $d['title_en'], $d['amount'], $d['min_order'], $d['valid_days'], $d['expires_on'], $d['is_active']];
        if ($id) {
            $values[] = $id;
            self::run('UPDATE coupons SET code = ?, title_bn = ?, title_en = ?, amount = ?, min_order = ?, valid_days = ?, expires_on = ?, is_active = ?
                       WHERE id = ?', $values);
            return;
        }
        $values[] = $by;
        self::run('INSERT INTO coupons (code, title_bn, title_en, amount, min_order, valid_days, expires_on, is_active, created_by)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $values);
    }

    public static function setCouponActive($id, $active)
    {
        self::run('UPDATE coupons SET is_active = ? WHERE id = ?', [$active, $id]);
    }

    // A coupon that was used can't be deleted (old bookings point to it): the admin hides it instead.
    // Returns false when it was not deleted.
    public static function deleteCoupon($id)
    {
        if (self::one('SELECT 1 AS x FROM bookings WHERE coupon_id = ? LIMIT 1', [$id])) {
            return false;
        }
        self::run('DELETE FROM coupons WHERE id = ?', [$id]);
        return true;
    }

    // ------------------------------------------------------------ COUPONS (user)

    // When this user opened their account (valid_days counts from here)
    private static function accountOpened($userId)
    {
        return self::one('SELECT created_at FROM users WHERE id = ?', [$userId])['created_at'];
    }

    // A last day (Y-m-d) that has already gone? null = no end, never gone.
    private static function isPast($lastDay)
    {
        return $lastDay !== null && $lastDay < date('Y-m-d');
    }

    // Last day this user can use it (null = no end): the earlier of valid_days and expires_on
    public static function lastDay(array $c, $userCreatedAt)
    {
        $days = [];
        if ($c['valid_days'] !== null) {
            $days[] = date('Y-m-d', strtotime($userCreatedAt . ' +' . (int)$c['valid_days'] . ' days'));
        }
        if ($c['expires_on'] !== null) {
            $days[] = $c['expires_on'];
        }
        return $days ? min($days) : null;
    }

    // The user's coupons: every active one, with 'state' = usable / used / expired and 'last_day'.
    // Usable ones first, then used, then expired.
    public static function forUser($userId)
    {
        $opened = self::accountOpened($userId);
        $used = array_column(self::all('SELECT coupon_id FROM coupon_uses WHERE user_id = ?', [$userId]), 'coupon_id');

        $coupons = [];
        foreach (self::all('SELECT * FROM coupons WHERE is_active = 1 ORDER BY amount DESC, id ASC') as $c) {
            $c['last_day'] = self::lastDay($c, $opened);
            if (in_array($c['id'], $used)) {
                $c['state'] = 'used';
            } elseif (self::isPast($c['last_day'])) {
                $c['state'] = 'expired';
            } else {
                $c['state'] = 'usable';
            }
            $coupons[] = $c;
        }

        $rank = ['usable' => 0, 'used' => 1, 'expired' => 2];
        usort($coupons, function ($a, $b) use ($rank) {
            return $rank[$a['state']] <=> $rank[$b['state']];
        });
        return $coupons;
    }

    // Can this user use this coupon now on a service of this price? (on, price high enough, not run out, not used before)
    public static function couponUsable(array $c, $userId, $price)
    {
        if (!$c['is_active'] || (int)$price < (int)$c['min_order']) {
            return false;
        }
        if (self::isPast(self::lastDay($c, self::accountOpened($userId)))) {
            return false;
        }
        return !self::one('SELECT 1 AS x FROM coupon_uses WHERE coupon_id = ? AND user_id = ?', [$c['id'], $userId]);
    }
}
