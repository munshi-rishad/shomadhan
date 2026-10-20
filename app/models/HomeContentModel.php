<?php
// app/models/HomeContentModel.php
// MODEL for what the admin puts on the user home page:
// banners, popups, services, sub_services and notifications (offers).

class HomeContentModel
{
    // staff.id of the admin who is adding things now (saved in created_by). The admin panel sets it.
    public static $by = null;

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

    // ------------------------------------------------------------ SETTINGS (welcome page picture)
    const DEFAULT_WELCOME_BANNER = 'assets/images/coupon-banner.jpg';

    public static function setting($name, $default = '')
    {
        $row = self::one('SELECT value FROM settings WHERE name = ?', [$name]);
        return $row ? $row['value'] : $default;
    }

    public static function setSetting($name, $value)
    {
        self::run('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, $value]);
    }

    // ------------------------------------------------------------ POPUPS
    public static function popups()
    {
        return self::all('SELECT * FROM popups ORDER BY id');      // admin list: id low -> high
    }

    public static function popup($id)
    {
        return self::one('SELECT * FROM popups WHERE id = ?', [$id]);
    }

    // the one users see: newest popup that is turned on
    public static function activePopup()
    {
        return self::one('SELECT * FROM popups WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }

    public static function addPopup($image, $title, $link = '')
    {
        self::run('INSERT INTO popups (image, title, link, created_by) VALUES (?, ?, ?, ?)', [$image, $title !== '' ? $title : null, $link !== '' ? $link : null, self::$by]);
    }

    public static function updatePopup($id, $image, $title, $link = '')
    {
        self::run('UPDATE popups SET image = ?, title = ?, link = ? WHERE id = ?', [$image, $title !== '' ? $title : null, $link !== '' ? $link : null, $id]);
    }

    public static function setPopupActive($id, $active)
    {
        self::run('UPDATE popups SET is_active = ? WHERE id = ?', [$active, $id]);
    }

    public static function deletePopup($id)
    {
        self::run('DELETE FROM popups WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------------ BANNERS
    public static function banners($onlyActive = false)
    {
        return self::all('SELECT * FROM banners' . ($onlyActive ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id');
    }

    public static function banner($id)
    {
        return self::one('SELECT * FROM banners WHERE id = ?', [$id]);
    }

    public static function addBanner($image, $title, $order)
    {
        self::run('INSERT INTO banners (image, title, sort_order, created_by) VALUES (?, ?, ?, ?)', [$image, $title, $order, self::$by]);
    }

    public static function updateBanner($id, $title, $order, $active)
    {
        self::run('UPDATE banners SET title = ?, sort_order = ?, is_active = ? WHERE id = ?', [$title, $order, $active, $id]);
    }

    public static function deleteBanner($id)
    {
        self::run('DELETE FROM banners WHERE id = ?', [$id]);
    }

    // ----------------------------------------------------------- SERVICES
    public static function services($onlyActive = false)
    {
        return self::all(
            'SELECT s.*, (SELECT COUNT(*) FROM sub_services x WHERE x.service_id = s.id) AS sub_count
             FROM services s' . ($onlyActive ? ' WHERE s.is_active = 1' : '') . ' ORDER BY s.sort_order, s.id'
        );
    }

    public static function service($id)
    {
        return self::one('SELECT * FROM services WHERE id = ?', [$id]);
    }

    public static function saveService($id, array $d)
    {
        if ($id) {
            self::run('UPDATE services SET name_bn = ?, name_en = ?, icon = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?',
                [$d['name_bn'], $d['name_en'], $d['icon'], $d['color'], $d['sort_order'], $d['is_active'], $id]);
        } else {
            self::run('INSERT INTO services (name_bn, name_en, icon, color, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$d['name_bn'], $d['name_en'], $d['icon'], $d['color'], $d['sort_order'], $d['is_active'], self::$by]);
        }
    }

    public static function deleteService($id)
    {
        self::run('DELETE FROM services WHERE id = ?', [$id]);   // only when it has no sub-services (ON DELETE RESTRICT)
    }

    // ------------------------------------------------------- SUB-SERVICES
    private static function subSql($where = '')
    {
        return 'SELECT x.*, s.name_bn AS service_bn, s.name_en AS service_en, s.icon AS service_icon, s.color AS service_color
                FROM sub_services x JOIN services s ON s.id = x.service_id ' . $where;
    }

    public static function subServices($serviceId = 0)
    {
        return $serviceId
            ? self::all(self::subSql('WHERE x.service_id = ? ORDER BY x.id'), [$serviceId])
            : self::all(self::subSql('ORDER BY x.id'));          // admin list: id low -> high
    }

    public static function subService($id)
    {
        return self::one(self::subSql('WHERE x.id = ?'), [$id]);
    }

    public static function saveSubService($id, array $d)
    {
        $cols = [$d['service_id'], $d['name_bn'], $d['name_en'], $d['detail_bn'], $d['detail_en'], $d['image'],
                 $d['price'], $d['rating'], $d['rating_count'], $d['popular_rank'], $d['is_active']];
        if ($id) {
            $cols[] = $id;
            self::run('UPDATE sub_services SET service_id = ?, name_bn = ?, name_en = ?, detail_bn = ?, detail_en = ?, image = ?,
                       price = ?, rating = ?, rating_count = ?, popular_rank = ?, is_active = ? WHERE id = ?', $cols);
            return $id;
        } else {
            self::run('INSERT INTO sub_services (service_id, name_bn, name_en, detail_bn, detail_en, image, price, rating, rating_count, popular_rank, is_active, created_by)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_merge($cols, [self::$by]));
            return (int)self::db()->lastInsertId();
        }
    }

    public static function deleteSubService($id)
    {
        self::run('DELETE FROM sub_services WHERE id = ?', [$id]);
    }

    public static function setPopularRank($id, $rank)
    {
        self::run('UPDATE sub_services SET popular_rank = ? WHERE id = ?', [$rank, $id]);
    }

    // ---- what the user home page shows (only active services + active sub-services)
    private static function visible($extra, array $args = [], $limit = 0)
    {
        return self::all(self::subSql('WHERE x.is_active = 1 AND s.is_active = 1 ' . $extra) . ($limit ? ' LIMIT ' . (int)$limit : ''), $args);
    }

    public static function popular($limit = 0)
    {
        return self::visible('AND x.popular_rank IS NOT NULL ORDER BY x.popular_rank, x.id', [], $limit);
    }

    public static function newest($limit = 0)
    {
        return self::visible('ORDER BY x.created_at DESC, x.id DESC', [], $limit);
    }

    public static function inService($serviceId)
    {
        return self::visible('AND x.service_id = ? ORDER BY x.popular_rank IS NULL, x.popular_rank, x.id DESC', [$serviceId]);
    }

    public static function search($q)
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';
        return self::visible('AND (x.name_bn LIKE ? OR x.name_en LIKE ? OR x.detail_bn LIKE ? OR x.detail_en LIKE ? OR s.name_bn LIKE ? OR s.name_en LIKE ?)
                              ORDER BY x.popular_rank IS NULL, x.popular_rank, x.id DESC', array_fill(0, 6, $like), 40);
    }

    // ------------------------------------------------- NOTIFICATIONS (offers)
    // $order 'id' = admin list (id low -> high), otherwise newest first (user's bell)
    public static function notifications($onlyActive = false, $limit = 0, $order = 'newest')
    {
        return self::all('SELECT * FROM notifications' . ($onlyActive ? ' WHERE is_active = 1' : '')
                         . ($order === 'id' ? ' ORDER BY id' : ' ORDER BY created_at DESC, id DESC') . ($limit ? ' LIMIT ' . (int)$limit : ''));
    }

    public static function notification($id)
    {
        return self::one('SELECT * FROM notifications WHERE id = ?', [$id]);
    }

    public static function addNotification($title, $message, $image)
    {
        self::run('INSERT INTO notifications (title, message, image, created_by) VALUES (?, ?, ?, ?)', [$title, $message, $image ?: null, self::$by]);
    }

    public static function updateNotification($id, $title, $message, $image)
    {
        self::run('UPDATE notifications SET title = ?, message = ?, image = ? WHERE id = ?', [$title, $message, $image ?: null, $id]);
    }

    public static function setNotificationActive($id, $active)
    {
        self::run('UPDATE notifications SET is_active = ? WHERE id = ?', [$active, $id]);
    }

    public static function deleteNotification($id)
    {
        self::run('DELETE FROM notifications WHERE id = ?', [$id]);
    }

    // Red number on the bell: offers sent after the user last opened the list
    public static function unreadCount($userId)
    {
        $st = self::db()->prepare(
            'SELECT COUNT(*) FROM notifications n JOIN users u ON u.id = ?
             WHERE n.is_active = 1 AND (u.notifications_seen_at IS NULL OR n.created_at > u.notifications_seen_at)'
        );
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    public static function markNotificationsSeen($userId)
    {
        self::run('UPDATE users SET notifications_seen_at = NOW() WHERE id = ?', [$userId]);
    }
}
