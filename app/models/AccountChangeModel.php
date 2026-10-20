<?php
// app/models/AccountChangeModel.php
// MODEL for account_changes: when an admin changes someone's account (name, mobile, status, role ...),
// that person sees it in their notifications: who changed it, when, and what.
// changes = list of [field key, old value, new value] (saved as JSON). Field keys are the lang keys of the labels.

class AccountChangeModel
{
    private static function owner($role)
    {
        return $role === 'user' ? 'user_id' : 'staff_id';
    }

    public static function add($role, $id, $byStaffId, array $changes)
    {
        if (!$changes) {
            return;
        }
        Database::get()->prepare('INSERT INTO account_changes (' . self::owner($role) . ', changed_by, changes) VALUES (?, ?, ?)')
            ->execute([$id, $byStaffId, json_encode(array_values($changes), JSON_UNESCAPED_UNICODE)]);
    }

    // newest first, with the changer's name. Each row gets 'list' = decoded changes.
    public static function forAccount($role, $id, $limit = 30)
    {
        $st = Database::get()->prepare(
            'SELECT c.*, s.name AS by_name FROM account_changes c JOIN staff s ON s.id = c.changed_by
             WHERE c.' . self::owner($role) . ' = ? ORDER BY c.id DESC LIMIT ' . (int)$limit
        );
        $st->execute([$id]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['list'] = json_decode($r['changes'], true) ?: [];
        }
        return $rows;
    }

    public static function unseen($role, $id)
    {
        $st = Database::get()->prepare('SELECT COUNT(*) FROM account_changes WHERE ' . self::owner($role) . ' = ? AND seen_at IS NULL');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    public static function markSeen($role, $id)
    {
        Database::get()->prepare('UPDATE account_changes SET seen_at = NOW() WHERE ' . self::owner($role) . ' = ? AND seen_at IS NULL')->execute([$id]);
    }
}
