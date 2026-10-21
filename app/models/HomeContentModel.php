<?php
// app/models/HomeContentModel.php
// MODEL for what the admin puts on the website:
// settings + site info texts, popups, banners, services (categories), sub_services and notifications (offers).
// Used by the admin panel (add / edit / delete) and by the public and user pages (show only what is turned on).

class HomeContentModel
{
    // staff.id of the admin who is adding things now (saved in created_by). The admin panel sets it.
    public static $by = null;

    const DEFAULT_WELCOME_BANNER = 'assets/images/coupon-banner.jpg';

    // Sub-service lists for users: popular ones first (by rank), then the newest
    const ORDER_POPULAR_FIRST = 'ORDER BY x.popular_rank IS NULL, x.popular_rank, x.id DESC';

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

    // Optional text field: empty is saved as NULL
    private static function orNull($text)
    {
        return $text !== '' ? $text : null;
    }

    // " LIMIT n", or nothing when $limit is 0
    private static function limitSql($limit)
    {
        return $limit ? ' LIMIT ' . (int)$limit : '';
    }

    // ------------------------------------------------------------ SETTINGS (single values, e.g. the welcome page picture)

    public static function setting($name, $default = '')
    {
        $row = self::one('SELECT value FROM settings WHERE name = ?', [$name]);
        return $row ? $row['value'] : $default;
    }

    public static function setSetting($name, $value)
    {
        self::run('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, $value]);
    }

    // ------------------------------------------------------------ SITE INFO (contact, about, terms, privacy)

    // Built-in texts (app/data/site-defaults.php) + what the admin saved (settings: site_<key>).
    // If the database is off, the built-in texts are used.
    public static function site()
    {
        $site = require APP_PATH . '/data/site-defaults.php';
        try {
            foreach (self::all("SELECT name, value FROM settings WHERE name LIKE 'site\\_%'") as $row) {
                $key = substr($row['name'], 5);         // after "site_"
                if (array_key_exists($key, $site) && trim($row['value']) !== '') {
                    $site[$key] = $row['value'];
                }
            }
        } catch (PDOException $ex) {
            app_log('error', 'site info: ' . $ex->getMessage());
        }
        return $site;
    }

    // Real numbers for the About page: active categories, services, providers, areas (0 when the database is off)
    public static function stats()
    {
        try {
            return self::one("SELECT
                (SELECT COUNT(*) FROM services WHERE is_active = 1) AS categories,
                (SELECT COUNT(*) FROM sub_services WHERE is_active = 1) AS services,
                (SELECT COUNT(*) FROM providers WHERE status = 'active' AND deleted_at IS NULL) AS providers,
                (SELECT COUNT(*) FROM areas) AS areas");
        } catch (PDOException $ex) {
            app_log('error', 'about stats: ' . $ex->getMessage());
            return ['categories' => 0, 'services' => 0, 'providers' => 0, 'areas' => 0];
        }
    }

    // $d = [key => text], saved as settings site_<key>
    public static function saveSite(array $d)
    {
        foreach ($d as $key => $value) {
            self::setSetting('site_' . $key, $value);
        }
    }

    // ------------------------------------------------------------ POPUPS

    // Admin list: id low -> high
    public static function popups()
    {
        return self::all('SELECT * FROM popups ORDER BY id');
    }

    public static function popup($id)
    {
        return self::one('SELECT * FROM popups WHERE id = ?', [$id]);
    }

    // Every popup that is on (newest first); the user home shows them one after another
    public static function activePopups()
    {
        return self::all('SELECT * FROM popups WHERE is_active = 1 ORDER BY id DESC');
    }

    public static function addPopup($image, $title, $link = '')
    {
        self::run('INSERT INTO popups (image, title, link, created_by) VALUES (?, ?, ?, ?)',
            [$image, self::orNull($title), self::orNull($link), self::$by]);
    }

    public static function updatePopup($id, $image, $title, $link = '')
    {
        self::run('UPDATE popups SET image = ?, title = ?, link = ? WHERE id = ?',
            [$image, self::orNull($title), self::orNull($link), $id]);
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

    // Same order on the admin list and the user page; the admin changes it with the ▲ ▼ arrows
    public static function banners($onlyActive = false)
    {
        return self::all('SELECT * FROM banners' . ($onlyActive ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id');
    }

    public static function banner($id)
    {
        return self::one('SELECT * FROM banners WHERE id = ?', [$id]);
    }

    public static function addBanner($image, $title)
    {
        self::run('INSERT INTO banners (image, title, sort_order, created_by) VALUES (?, ?, ?, ?)',
            [$image, $title, self::nextOrder('banners'), self::$by]);
    }

    public static function updateBanner($id, $title, $active)
    {
        self::run('UPDATE banners SET title = ?, is_active = ? WHERE id = ?', [$title, $active, $id]);
    }

    public static function deleteBanner($id)
    {
        self::run('DELETE FROM banners WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------------ ORDER (banners + services)

    // ▲ ▼ arrows: move one row of banners / services one place up or down.
    // Rows are renumbered 1, 2, 3 ... so the order numbers stay clean.
    public static function move($table, $id, $dir)
    {
        if (!in_array($table, ['banners', 'services'], true)) {
            return;         // only these two tables have sort_order (and $table goes into the SQL)
        }
        $ids  = array_map('intval', array_column(self::all("SELECT id FROM $table ORDER BY sort_order, id"), 'id'));
        $from = array_search((int)$id, $ids, true);
        $to   = $dir === 'up' ? $from - 1 : $from + 1;
        if ($from === false || !isset($ids[$to])) {
            return;         // unknown id, or already at the top / bottom
        }
        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
        foreach ($ids as $position => $rowId) {
            self::run("UPDATE $table SET sort_order = ? WHERE id = ?", [$position + 1, $rowId]);
        }
    }

    // A new banner / service goes to the end of the list
    private static function nextOrder($table)
    {
        $row = self::one("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM $table");
        return (int)$row['n'];
    }

    // ----------------------------------------------------------- SERVICES (categories)

    // With sub_count = how many sub-services each has. Same order for admin and users (▲ ▼ arrows).
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

    // Add ($id = 0, goes to the end of the list) or edit
    public static function saveService($id, array $d)
    {
        if ($id) {
            self::run('UPDATE services SET name_bn = ?, name_en = ?, icon = ?, color = ?, is_active = ? WHERE id = ?',
                [$d['name_bn'], $d['name_en'], $d['icon'], $d['color'], $d['is_active'], $id]);
            return;
        }
        self::run('INSERT INTO services (name_bn, name_en, icon, color, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['name_bn'], $d['name_en'], $d['icon'], $d['color'], self::nextOrder('services'), $d['is_active'], self::$by]);
    }

    // Works only when it has no sub-services (ON DELETE RESTRICT)
    public static function deleteService($id)
    {
        self::run('DELETE FROM services WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------- SUB-SERVICES

    // A sub-service row + its category's name, icon and color
    private static function subSql($where = '')
    {
        return 'SELECT x.*, s.name_bn AS service_bn, s.name_en AS service_en, s.icon AS service_icon, s.color AS service_color
                FROM sub_services x JOIN services s ON s.id = x.service_id ' . $where;
    }

    // Admin list (id low -> high): all, or only one category's
    public static function subServices($serviceId = 0)
    {
        if ($serviceId) {
            return self::all(self::subSql('WHERE x.service_id = ? ORDER BY x.id'), [$serviceId]);
        }
        return self::all(self::subSql('ORDER BY x.id'));
    }

    public static function subService($id)
    {
        return self::one(self::subSql('WHERE x.id = ?'), [$id]);
    }

    // Add ($id = 0) or edit. Returns the sub-service's id.
    public static function saveSubService($id, array $d)
    {
        $values = [$d['service_id'], $d['name_bn'], $d['name_en'], $d['detail_bn'], $d['detail_en'], $d['image'],
                   $d['price'], $d['rating'], $d['rating_count'], $d['popular_rank'], $d['is_active']];
        if ($id) {
            $values[] = $id;
            self::run('UPDATE sub_services SET service_id = ?, name_bn = ?, name_en = ?, detail_bn = ?, detail_en = ?, image = ?,
                       price = ?, rating = ?, rating_count = ?, popular_rank = ?, is_active = ? WHERE id = ?', $values);
            return $id;
        }
        $values[] = self::$by;
        self::run('INSERT INTO sub_services (service_id, name_bn, name_en, detail_bn, detail_en, image, price, rating, rating_count, popular_rank, is_active, created_by)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $values);
        return (int)self::db()->lastInsertId();
    }

    public static function deleteSubService($id)
    {
        self::run('DELETE FROM sub_services WHERE id = ?', [$id]);
    }

    public static function setPopularRank($id, $rank)
    {
        self::run('UPDATE sub_services SET popular_rank = ? WHERE id = ?', [$rank, $id]);
    }

    // ---- What the user pages show (only active services + active sub-services)

    // $extra = more conditions (starting with AND) + ORDER BY
    private static function visible($extra, array $args = [], $limit = 0)
    {
        return self::all(self::subSql('WHERE x.is_active = 1 AND s.is_active = 1 ' . $extra) . self::limitSql($limit), $args);
    }

    // Only the ones with a popular rank, by rank
    public static function popular($limit = 0)
    {
        return self::visible('AND x.popular_rank IS NOT NULL ORDER BY x.popular_rank, x.id', [], $limit);
    }

    public static function newest($limit = 0)
    {
        return self::visible('ORDER BY x.created_at DESC, x.id DESC', [], $limit);
    }

    // One category's page
    public static function inService($serviceId)
    {
        return self::visible('AND x.service_id = ? ' . self::ORDER_POPULAR_FIRST, [$serviceId]);
    }

    // Search box: name or detail of the sub-service, or its category's name (at most 40 results)
    public static function search($q)
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        return self::visible('AND (x.name_bn LIKE ? OR x.name_en LIKE ? OR x.detail_bn LIKE ? OR x.detail_en LIKE ? OR s.name_bn LIKE ? OR s.name_en LIKE ?)
                              ' . self::ORDER_POPULAR_FIRST, array_fill(0, 6, $like), 40);
    }

    // ------------------------------------------------- NOTIFICATIONS (offers)

    // $order 'id' = admin list (id low -> high), otherwise newest first (user's bell)
    public static function notifications($onlyActive = false, $limit = 0, $order = 'newest')
    {
        return self::all('SELECT * FROM notifications' . ($onlyActive ? ' WHERE is_active = 1' : '')
                         . ($order === 'id' ? ' ORDER BY id' : ' ORDER BY created_at DESC, id DESC')
                         . self::limitSql($limit));
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
