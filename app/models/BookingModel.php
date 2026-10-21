<?php
// app/models/BookingModel.php
// MODEL for bookings: a user books a sub-service, the office (admin / employee) sends a provider.
//   pending -> ongoing (provider sent) -> completed (reward points)      or  -> cancelled (wallet money back, coupon free again)
// Money parts (wallet payment / refund, coupon use) are saved in the SAME transaction as the booking,
// so a booking can never be saved without its payment, or the other way round.
// Used by the user's booking pages and the office bookings page.

require_once APP_PATH . '/models/WalletModel.php';

class BookingModel
{
    const STATUSES = ['pending', 'ongoing', 'completed', 'cancelled'];
    const POINTS_PER_TAKA = 0.1;        // 1 reward point for every ৳10 of a completed booking

    // Pending / ongoing lists: soonest first (what happens next)
    const ORDER_SOONEST = 'b.scheduled_at ASC, b.id ASC';

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

    // Runs $work($db) inside one transaction: everything is saved, or (on any error) nothing is.
    private static function transaction(callable $work)
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $result = $work($db);
            $db->commit();
            return $result;
        } catch (Throwable $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    // Text for a LIKE search: % and _ typed by the user are searched as normal letters
    private static function likePattern($q)
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    }

    // Booking id typed in a search box ("12", "#12", "SHM00012", "#SHM00012"), or 0
    private static function idFromSearch($q)
    {
        return preg_match('/^#?(SHM)?0*(\d+)$/i', $q, $m) ? (int)$m[2] : 0;
    }

    // [status => count] for every status (0 when none), from rows of (status, n)
    private static function statusCounts($sql, array $args = [])
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach (self::all($sql, $args) as $row) {
            $counts[$row['status']] = (int)$row['n'];
        }
        return $counts;
    }

    // "SHM00012" style booking number (from the id, so it is always unique)
    public static function code($id)
    {
        return 'SHM' . str_pad((string)(int)$id, 5, '0', STR_PAD_LEFT);
    }

    // A booking row + service names, picture, category, provider, user (for lists and cards)
    private static function sql($where, $order = 'b.id DESC')
    {
        return 'SELECT b.*, x.name_bn, x.name_en, x.detail_bn, x.detail_en, x.image, x.service_id,
                       s.name_bn AS service_bn, s.name_en AS service_en, s.icon AS service_icon, s.color AS service_color,
                       p.name AS provider_name, p.mobile AS provider_mobile, p.photo AS provider_photo,
                       u.name AS user_name, u.mobile AS user_mobile, c.code AS coupon_code, h.name AS handled_name
                FROM bookings b
                JOIN sub_services x ON x.id = b.sub_service_id
                JOIN services s ON s.id = x.service_id
                JOIN users u ON u.id = b.user_id
                LEFT JOIN providers p ON p.id = b.provider_id
                LEFT JOIN coupons c ON c.id = b.coupon_id
                LEFT JOIN staff h ON h.id = b.handled_by
                ' . $where . ' ORDER BY ' . $order;
    }

    // ------------------------------------------------------------ READ

    public static function find($id)
    {
        return self::one(self::sql('WHERE b.id = ?'), [$id]);
    }

    // One booking of this user only (a user can never open someone else's booking)
    public static function findForUser($id, $userId)
    {
        return self::one(self::sql('WHERE b.id = ? AND b.user_id = ?'), [$id, $userId]);
    }

    // A user's bookings with one status. Pending / ongoing: soonest first; completed / cancelled: newest first
    public static function forUser($userId, $status)
    {
        $order = in_array($status, ['pending', 'ongoing'], true) ? self::ORDER_SOONEST : 'b.scheduled_at DESC, b.id DESC';
        return self::all(self::sql('WHERE b.user_id = ? AND b.status = ?', $order), [$userId, $status]);
    }

    // ['pending' => 2, 'ongoing' => 1, ...] for the tabs
    public static function countsForUser($userId)
    {
        return self::statusCounts('SELECT status, COUNT(*) AS n FROM bookings WHERE user_id = ? GROUP BY status', [$userId]);
    }

    // All reward points of a user (from completed bookings)
    public static function points($userId)
    {
        $row = self::one('SELECT COALESCE(SUM(points), 0) AS n FROM bookings WHERE user_id = ? AND status = "completed"', [$userId]);
        return (int)$row['n'];
    }

    // The completed bookings that gave points, newest first
    public static function pointsList($userId)
    {
        return self::all(self::sql('WHERE b.user_id = ? AND b.status = "completed" AND b.points > 0', 'b.scheduled_at DESC'), [$userId]);
    }

    // ------------------------------------------------------------ USER: new booking
    // $d = user_id, sub_service_id, scheduled_at, area, address, note, price, coupon (row or null), payment
    // Returns the new id. Throws RuntimeException('wallet' | 'coupon') when the money / coupon is no longer OK.
    public static function create(array $d)
    {
        return self::transaction(function (PDO $db) use ($d) {
            // lock this user's row: two bookings at the same moment can't both spend the same wallet money
            $db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$d['user_id']]);

            // coupon: checked again here (it may have been used in another tab)
            $discount = 0;
            if ($d['coupon']) {
                if (!WalletModel::couponUsable($d['coupon'], $d['user_id'], $d['price'])) {
                    throw new RuntimeException('coupon');
                }
                $discount = min((int)$d['coupon']['amount'], (int)$d['price']);
            }

            $total = (int)$d['price'] - $discount;
            $payByWallet = $d['payment'] === 'wallet';
            if ($payByWallet && WalletModel::balance($d['user_id']) < $total) {
                throw new RuntimeException('wallet');
            }

            $db->prepare('INSERT INTO bookings (user_id, sub_service_id, scheduled_at, area, address, note, price, discount, total, coupon_id, payment)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$d['user_id'], $d['sub_service_id'], $d['scheduled_at'], $d['area'], $d['address'], $d['note'],
                          $d['price'], $discount, $total, $d['coupon']['id'] ?? null, $d['payment']]);
            $id = (int)$db->lastInsertId();

            if ($d['coupon']) {
                $db->prepare('INSERT INTO coupon_uses (coupon_id, user_id, booking_id) VALUES (?, ?, ?)')
                   ->execute([$d['coupon']['id'], $d['user_id'], $id]);
            }
            if ($payByWallet && $total > 0) {
                WalletModel::addTx($d['user_id'], 'payment', -$total, $id);
            }
            return $id;
        });
    }

    // ------------------------------------------------------------ CANCEL (user: pending only; office: pending / ongoing)
    // Wallet money comes back (refund row) and the coupon can be used again. Returns true when it was cancelled now.
    public static function cancel(array $b, $by, $staffId = null, $reason = null)
    {
        return self::transaction(function (PDO $db) use ($b, $by, $staffId, $reason) {
            // the user can cancel only while it is pending (a provider may already be on the way after that); the office also when ongoing
            $allowed = $by === 'user' ? '("pending")' : '("pending", "ongoing")';
            $st = $db->prepare('UPDATE bookings SET status = "cancelled", cancelled_by = ?, cancel_reason = ?, handled_by = COALESCE(?, handled_by)
                                WHERE id = ? AND status IN ' . $allowed);
            $st->execute([$by, $reason, $staffId, $b['id']]);
            $cancelled = $st->rowCount() === 1;

            if ($cancelled) {
                // what was paid from the wallet for this booking (payments are minus, so -SUM)
                $paidRow = self::one('SELECT COALESCE(-SUM(amount), 0) AS n FROM wallet_tx WHERE booking_id = ? AND status = "successful"', [$b['id']]);
                $paid = (int)$paidRow['n'];
                if ($paid > 0) {
                    WalletModel::addTx($b['user_id'], 'refund', $paid, $b['id']);
                }
                $db->prepare('DELETE FROM coupon_uses WHERE booking_id = ?')->execute([$b['id']]);
            }
            return $cancelled;
        });
    }

    // ------------------------------------------------------------ USER: new date / time (pending only)
    public static function reschedule($id, $userId, $when)
    {
        $st = self::db()->prepare('UPDATE bookings SET scheduled_at = ? WHERE id = ? AND user_id = ? AND status = "pending"');
        $st->execute([$when, $id, $userId]);
        return $st->rowCount() === 1;
    }

    // ------------------------------------------------------------ OFFICE

    // Send a provider (pending -> ongoing). Also used to change the provider of an ongoing booking.
    public static function assign($id, $providerId, $staffId)
    {
        $st = self::db()->prepare('UPDATE bookings SET provider_id = ?, status = "ongoing", handled_by = ? WHERE id = ? AND status IN ("pending", "ongoing")');
        $st->execute([$providerId, $staffId, $id]);
        return $st->rowCount() === 1;
    }

    // Work done (ongoing -> completed): the user gets reward points
    public static function complete(array $b, $staffId)
    {
        $points = (int)floor((int)$b['total'] * self::POINTS_PER_TAKA);
        $st = self::db()->prepare('UPDATE bookings SET status = "completed", points = ?, handled_by = ? WHERE id = ? AND status = "ongoing"');
        $st->execute([$points, $staffId, $b['id']]);
        return $st->rowCount() === 1;
    }

    // Who can be sent: active providers who give this sub-service; if none is linked yet, active providers of the same category
    public static function providersFor(array $b)
    {
        $rows = self::all('SELECT p.id, p.name, p.mobile, p.area FROM providers p JOIN sub_service_providers sp ON sp.provider_id = p.id
                           WHERE sp.sub_service_id = ? AND p.status = "active" AND p.deleted_at IS NULL ORDER BY p.name', [$b['sub_service_id']]);
        if ($rows) {
            return $rows;
        }
        return self::all('SELECT id, name, mobile, area FROM providers WHERE service_id = ? AND status = "active" AND deleted_at IS NULL ORDER BY name', [$b['service_id']]);
    }

    // Office list: status tab + search (booking number, user name / mobile, service, area, provider) + pages of $per.
    // Returns [rows, total, page, pages].
    public static function adminList($status, $q, $page, $per = 25)
    {
        $where = 'WHERE 1';
        $args = [];
        if (in_array($status, self::STATUSES, true)) {
            $where .= ' AND b.status = ?';
            $args[] = $status;
        }
        $q = trim((string)$q);
        if ($q !== '') {
            $like = self::likePattern($q);
            $where .= ' AND (b.id = ? OR u.name LIKE ? OR u.mobile LIKE ? OR x.name_bn LIKE ? OR x.name_en LIKE ? OR b.area LIKE ? OR p.name LIKE ?)';
            array_push($args, self::idFromSearch($q), $like, $like, $like, $like, $like, $like);
        }

        // count first, so a page number past the end can be pulled back to the last page
        $count = self::db()->prepare('SELECT COUNT(*) FROM bookings b JOIN sub_services x ON x.id = b.sub_service_id JOIN users u ON u.id = b.user_id
                                      LEFT JOIN providers p ON p.id = b.provider_id ' . $where);
        $count->execute($args);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page = min(max(1, (int)$page), $pages);

        // waiting ones: soonest first (what to do next); finished ones: newest first
        $order = in_array($status, ['pending', 'ongoing'], true) ? self::ORDER_SOONEST : 'b.id DESC';
        $rows = self::all(self::sql($where, $order) . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
        return [$rows, $total, $page, $pages];
    }

    // ['pending' => 4, ...] for all bookings (office tabs and menu numbers)
    public static function countByStatus()
    {
        return self::statusCounts('SELECT status, COUNT(*) AS n FROM bookings GROUP BY status');
    }
}
