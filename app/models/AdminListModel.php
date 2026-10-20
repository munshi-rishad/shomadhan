<?php
// app/models/AdminListModel.php
// MODEL for the admin lists (users, employees, admins, service providers, products) with search, sort and pages.
// Only the columns below can be searched or sorted (safe: user input never goes into the SQL as a column name).
// Passwords are never selected.

class AdminListModel
{
    const PER_PAGE = 25;

    // kind => [FROM part, columns to select, columns searched, sort key => SQL column, filters]
    //   'role' = staff of this role only, 'soft' = has deleted_at (deleted rows only when the status filter says "deleted")
    private static function kinds($lang)
    {
        $n = $lang === 'en' ? 'en' : 'bn';
        // people lists: users table, or staff table of one role. Staff also show which admin added them ("cb")
        $people = function ($table, $role, $extra = []) {
            $byAdmin = $table === 'staff';
            $sort = ['id' => 't.id', 'name' => 't.name', 'mobile' => 't.mobile', 'email' => 't.email'] + $extra
                  + ['status' => 't.status', 'created_at' => 't.created_at', 'last_login_at' => 't.last_login_at']
                  + ($byAdmin ? ['created_by' => 'cb.name'] : []);
            $select = ['t.deleted_at', 't.photo', 'db.name AS deleted_by'] + ($byAdmin ? [3 => 'cb.deleted_at AS created_by_gone'] : []);
            foreach ($sort as $key => $col) { $select[] = $col . ' AS ' . $key; }
            return [
                'from'    => $table . ' t LEFT JOIN staff db ON db.id = t.deleted_by' . ($byAdmin ? ' LEFT JOIN staff cb ON cb.id = t.created_by' : ''),
                'role'    => $role,
                'soft'    => true,
                'select'  => implode(', ', $select),
                'search'  => ['t.name', 't.mobile', 't.email'] + (isset($extra['area']) ? [3 => 't.area'] : []),
                'sort'    => $sort,
                'filters' => ['status' => 't.status'] + (isset($extra['area']) ? ['gender' => 't.gender', 'area' => 't.area'] : []),
            ];
        };
        return [
            'users'     => $people('users', null, ['area' => 't.area', 'gender' => 't.gender']),
            'employees' => $people('staff', 'employee'),
            'admins'    => $people('staff', 'admin'),
            'providers' => [
                'from'   => 'providers t JOIN services s ON s.id = t.service_id LEFT JOIN staff db ON db.id = t.deleted_by LEFT JOIN staff cb ON cb.id = t.created_by',
                'soft'   => true,
                'select' => 't.id, t.name, t.mobile, t.age, t.gender, t.area, t.address, t.photo, t.note, t.status, t.created_at, t.deleted_at,
                             s.name_' . $n . ' AS service, db.name AS deleted_by, cb.name AS created_by, cb.deleted_at AS created_by_gone',
                'search' => ['t.name', 't.mobile', 't.area', 't.address', 's.name_bn', 's.name_en'],
                'sort'   => [
                    'id' => 't.id', 'name' => 't.name', 'mobile' => 't.mobile', 'service' => 's.name_' . $n, 'age' => 't.age',
                    'gender' => 't.gender', 'area' => 't.area', 'status' => 't.status', 'created_at' => 't.created_at', 'created_by' => 'cb.name',
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
                    'rating' => 'x.rating', 'popular_rank' => 'x.popular_rank', 'providers' => 'providers', 'is_active' => 'x.is_active', 'created_at' => 'x.created_at',
                ],
                'filters' => ['service' => 'x.service_id', 'shown' => 'x.is_active'],
            ],
        ];
    }

    public static function sortKeys($kind, $lang)
    {
        return array_keys(self::kinds($lang)[$kind]['sort']);
    }

    // filter dropdowns this list has (e.g. users: status, gender, area)
    public static function filterKeys($kind)
    {
        return array_keys(self::kinds('bn')[$kind]['filters']);
    }

    // returns [rows, total, page, pages, sort, dir]. $filters: ['status' => 'inactive', ...] ('' = all)
    public static function find($kind, $lang, $q, $sort, $dir, $page, array $filters = [])
    {
        $k = self::kinds($lang)[$kind];
        $sort = isset($k['sort'][$sort]) ? $sort : 'id';
        $dir  = $dir === 'desc' ? 'desc' : 'asc';      // default: id low -> high

        $where = '';
        $args  = [];
        $q = trim((string)$q);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where = ' WHERE (' . implode(' OR ', array_map(function ($c) { return $c . ' LIKE ?'; }, $k['search'])) . ')';
            $args = array_fill(0, count($k['search']), $like);
        }

        if (!empty($k['role'])) {                           // staff of this role
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . 't.role = ?';
            $args[] = $k['role'];
        }
        if (!empty($k['soft'])) {                           // deleted ones only when the status filter says "deleted"
            $where .= ($where === '' ? ' WHERE ' : ' AND ') . 't.deleted_at IS ' . (($filters['status'] ?? '') === 'deleted' ? 'NOT NULL' : 'NULL');
        }
        foreach ($k['filters'] as $key => $col) {           // only known filters, value goes in as a ? parameter
            $v = (string)($filters[$key] ?? '');
            if ($key === 'status' && $v === 'deleted') { continue; }
            if ($v !== '') {
                $where .= ($where === '' ? ' WHERE ' : ' AND ') . $col . ' = ?';
                $args[] = $v;
            }
        }

        $db = Database::get();
        $st = $db->prepare('SELECT COUNT(*) FROM ' . $k['from'] . $where);
        $st->execute($args);
        $total = (int)$st->fetchColumn();
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int)$page), $pages);

        $col = $k['sort'][$sort];
        $order = in_array($sort, ['popular_rank', 'last_login_at', 'created_by'], true)   // empty (not popular / never logged in / no admin) always goes last
            ? $col . ' IS NULL, ' . $col . ' ' . $dir
            : $col . ' ' . $dir;
        $sql = 'SELECT ' . $k['select'] . ' FROM ' . $k['from'] . $where
             . ' ORDER BY ' . $order . ($sort === 'id' ? '' : ', ' . $k['sort']['id'] . ' ASC')
             . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE);
        $st = $db->prepare($sql);
        $st->execute($args);
        return [$st->fetchAll(), $total, $page, $pages, $sort, $dir];
    }
}
