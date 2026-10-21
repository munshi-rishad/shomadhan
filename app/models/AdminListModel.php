<?php
// app/models/AdminListModel.php
// MODEL for the admin lists (users, employees, admins, service providers, products) with search, sort, filters and pages.
// Used by the admin panel list pages (app/views/admin/list.php).
// Only the columns listed in kinds() can be searched, sorted or filtered: user input picks a key from these lists
// and never goes into the SQL as a column name. Passwords are never selected.

class AdminListModel
{
    const PER_PAGE = 25;

    // Sorting by these puts empty values (not popular / never logged in / no admin) last in both directions
    const EMPTY_LAST = ['popular_rank', 'last_login_at', 'created_by'];

    // ---- What each list reads from the database
    // kind => [
    //   'from'    => FROM part (tables + joins),
    //   'select'  => columns to select,
    //   'search'  => columns the search box looks in,
    //   'sort'    => sort key => SQL column,
    //   'filters' => filter key => SQL column,
    //   'role'    => staff of this role only (staff lists),
    //   'soft'    => table has deleted_at (deleted rows only show when the status filter says "deleted"),
    // ]
    private static function kinds($lang)
    {
        $n = $lang === 'en' ? 'en' : 'bn';

        // People lists: the users table, or the staff table for one role.
        // Staff rows also show which admin added them ("cb" join).
        $people = function ($table, $role, $extra = []) {
            $byAdmin = $table === 'staff';
            $hasArea = isset($extra['area']);

            $sort = ['id' => 't.id', 'name' => 't.name', 'mobile' => 't.mobile', 'email' => 't.email']
                  + $extra
                  + ['status' => 't.status', 'created_at' => 't.created_at', 'last_login_at' => 't.last_login_at']
                  + ($byAdmin ? ['created_by' => 'cb.name'] : []);

            $select = ['t.deleted_at', 't.photo', 'db.name AS deleted_by']
                    + ($byAdmin ? [3 => 'cb.deleted_at AS created_by_gone'] : []);
            foreach ($sort as $key => $col) {
                $select[] = $col . ' AS ' . $key;
            }

            $from = $table . ' t LEFT JOIN staff db ON db.id = t.deleted_by'
                  . ($byAdmin ? ' LEFT JOIN staff cb ON cb.id = t.created_by' : '');

            return [
                'from'    => $from,
                'role'    => $role,
                'soft'    => true,
                'select'  => implode(', ', $select),
                'search'  => ['t.name', 't.mobile', 't.email'] + ($hasArea ? [3 => 't.area'] : []),
                'sort'    => $sort,
                'filters' => ['status' => 't.status'] + ($hasArea ? ['gender' => 't.gender', 'area' => 't.area'] : []),
            ];
        };

        $personExtra = ['area' => 't.area', 'gender' => 't.gender'];

        return [
            'users'     => $people('users', null, $personExtra),
            'employees' => $people('staff', 'employee', $personExtra),
            'admins'    => $people('staff', 'admin', $personExtra),
            'providers' => [
                'from'   => 'providers t JOIN services s ON s.id = t.service_id LEFT JOIN staff db ON db.id = t.deleted_by LEFT JOIN staff cb ON cb.id = t.created_by',
                'soft'   => true,
                'select' => 't.id, t.name, t.mobile, t.email, t.age, t.gender, t.area, t.address, t.photo, t.note, t.status, t.created_at, t.deleted_at,
                             s.name_' . $n . ' AS service, db.name AS deleted_by, cb.name AS created_by, cb.deleted_at AS created_by_gone',
                'search' => ['t.name', 't.mobile', 't.email', 't.area', 't.address', 's.name_bn', 's.name_en'],
                'sort'   => [
                    'id' => 't.id', 'name' => 't.name', 'mobile' => 't.mobile', 'email' => 't.email',
                    'service' => 's.name_' . $n, 'age' => 't.age', 'gender' => 't.gender', 'area' => 't.area',
                    'status' => 't.status', 'created_at' => 't.created_at', 'created_by' => 'cb.name',
                ],
                'filters' => ['status' => 't.status', 'service' => 't.service_id', 'gender' => 't.gender', 'area' => 't.area'],
            ],
            'products'  => [
                'from'   => 'sub_services x JOIN services s ON s.id = x.service_id',
                'select' => 'x.id, x.name_bn, x.name_en, x.image, x.price, x.rating, x.rating_count, x.popular_rank, x.is_active, x.created_at,
                             s.name_bn AS service_bn, s.name_en AS service_en, s.icon AS service_icon, s.color AS service_color,
                             (SELECT COUNT(*) FROM sub_service_providers sp JOIN providers p ON p.id = sp.provider_id
                               WHERE sp.sub_service_id = x.id AND p.deleted_at IS NULL) AS providers',
                'search' => ['x.name_bn', 'x.name_en', 's.name_bn', 's.name_en'],
                'sort'   => [
                    'id' => 'x.id', 'name' => 'x.name_' . $n, 'service' => 's.name_' . $n, 'price' => 'x.price',
                    'rating' => 'x.rating', 'popular_rank' => 'x.popular_rank', 'providers' => 'providers',
                    'is_active' => 'x.is_active', 'created_at' => 'x.created_at',
                ],
                'filters' => ['service' => 'x.service_id', 'shown' => 'x.is_active'],
            ],
        ];
    }

    // ---- Column keys the list can be sorted by (the clickable table headers)
    public static function sortKeys($kind, $lang)
    {
        return array_keys(self::kinds($lang)[$kind]['sort']);
    }

    // ---- Filter dropdowns this list has (e.g. users: status, gender, area)
    public static function filterKeys($kind)
    {
        return array_keys(self::kinds('bn')[$kind]['filters']);
    }

    // Adds one condition to a WHERE part ("WHERE" the first time, "AND" after that)
    private static function addWhere(&$where, $condition)
    {
        $where .= ($where === '' ? ' WHERE ' : ' AND ') . $condition;
    }

    // ---- One page of a list
    // Returns [rows, total, page, pages, sort, dir]. $filters: ['status' => 'inactive', ...] ('' = all).
    // Unknown sort keys fall back to id; the default order is id low -> high.
    public static function find($kind, $lang, $q, $sort, $dir, $page, array $filters = [])
    {
        $list = self::kinds($lang)[$kind];
        $sort = is_string($sort) && isset($list['sort'][$sort]) ? $sort : 'id';
        $dir  = $dir === 'desc' ? 'desc' : 'asc';

        $where = '';
        $args  = [];

        // Search box: any of the search columns contains the text
        $q = trim((string)$q);
        if ($q !== '') {
            $like  = '%' . addcslashes($q, '%_\\') . '%';
            $likes = array_map(function ($col) { return $col . ' LIKE ?'; }, $list['search']);
            self::addWhere($where, '(' . implode(' OR ', $likes) . ')');
            $args = array_fill(0, count($list['search']), $like);
        }

        // Staff lists: only this role
        if (!empty($list['role'])) {
            self::addWhere($where, 't.role = ?');
            $args[] = $list['role'];
        }

        // Soft-deleted rows only when the status filter says "deleted", otherwise only live rows
        $showDeleted = ($filters['status'] ?? '') === 'deleted';
        if (!empty($list['soft'])) {
            self::addWhere($where, 't.deleted_at IS ' . ($showDeleted ? 'NOT NULL' : 'NULL'));
        }

        // Filter dropdowns: only known keys, the value always goes in as a ? parameter
        foreach ($list['filters'] as $key => $col) {
            $value = (string)($filters[$key] ?? '');
            if ($key === 'status' && $value === 'deleted') {
                continue;       // already handled above
            }
            if ($value !== '') {
                self::addWhere($where, $col . ' = ?');
                $args[] = $value;
            }
        }

        // Count first, so a page number past the end can be pulled back to the last page
        $db = Database::get();
        $st = $db->prepare('SELECT COUNT(*) FROM ' . $list['from'] . $where);
        $st->execute($args);
        $total = (int)$st->fetchColumn();
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int)$page), $pages);

        // ORDER BY: chosen column (empty ones last for some), then id so equal values keep a fixed order
        $col   = $list['sort'][$sort];
        $order = in_array($sort, self::EMPTY_LAST, true)
            ? $col . ' IS NULL, ' . $col . ' ' . $dir
            : $col . ' ' . $dir;
        if ($sort !== 'id') {
            $order .= ', ' . $list['sort']['id'] . ' ASC';
        }

        $sql = 'SELECT ' . $list['select'] . ' FROM ' . $list['from'] . $where
             . ' ORDER BY ' . $order
             . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE);
        $st = $db->prepare($sql);
        $st->execute($args);
        return [$st->fetchAll(), $total, $page, $pages, $sort, $dir];
    }
}
