<?php
// app/models/ActivityLogModel.php
// MODEL for activity_log: every change an admin / employee saves on the dashboard (who, which page, what, which row, when).
// Written by AdminController::done() after a save that worked; read by the admin "Work log" page (?page=log).
// Only names of actions are kept, never passwords or form values.

class ActivityLogModel
{
    const PER_PAGE = 30;

    // ---- One saved change
    public static function add($staffId, $page, $action, $targetId, $message)
    {
        Database::get()
            ->prepare('INSERT INTO activity_log (staff_id, page, action, target_id, message) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int)$staffId, substr($page, 0, 30), substr($action, 0, 30), $targetId ?: null, mb_substr($message, 0, 300)]);
    }

    // ---- Newest first, filtered by staff and / or page. Returns [rows, total, page, pageCount].
    public static function search($staffId, $page, $p)
    {
        $where = [];
        $args  = [];
        if ($staffId) {
            $where[] = 'l.staff_id = ?';
            $args[]  = (int)$staffId;
        }
        if ($page !== '') {
            $where[] = 'l.page = ?';
            $args[]  = $page;
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $db = Database::get();
        $st = $db->prepare('SELECT COUNT(*) FROM activity_log l' . $sqlWhere);
        $st->execute($args);
        $total = (int)$st->fetchColumn();
        $pageCount = max(1, (int)ceil($total / self::PER_PAGE));
        $p = min(max(1, (int)$p), $pageCount);
        $st = $db->prepare('SELECT l.*, s.name AS staff_name, s.role AS staff_role FROM activity_log l
                            LEFT JOIN staff s ON s.id = l.staff_id' . $sqlWhere . '
                            ORDER BY l.id DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($p - 1) * self::PER_PAGE));
        $st->execute($args);
        return [$st->fetchAll(), $total, $p, $pageCount];
    }

    // ---- Staff who have at least one row (for the filter)
    public static function staffList()
    {
        return Database::get()->query('SELECT DISTINCT s.id, s.name, s.role FROM activity_log l JOIN staff s ON s.id = l.staff_id ORDER BY s.name')->fetchAll();
    }
}
