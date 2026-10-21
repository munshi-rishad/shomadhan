<?php
// app/models/AccountChangeModel.php
// MODEL for account_changes: when an admin changes someone's account (name, mobile, status, role ...),
// that person sees it in their notifications: who changed it, when, and what.
// changes = list of [field key, old value, new value] (saved as JSON). Field keys are the lang keys of the labels.
// Used by the admin panel (writes a row after an edit) and the notifications page (reads them).

class AccountChangeModel
{
    // Customers are linked by user_id, admins / employees by staff_id
    private static function ownerColumn($role)
    {
        return $role === 'user' ? 'user_id' : 'staff_id';
    }

    // ---- Save one admin edit (nothing is saved when nothing changed)
    public static function add($role, $id, $byStaffId, array $changes)
    {
        if (!$changes) {
            return;
        }
        $json = json_encode(array_values($changes), JSON_UNESCAPED_UNICODE);
        Database::get()
            ->prepare('INSERT INTO account_changes (' . self::ownerColumn($role) . ', changed_by, changes) VALUES (?, ?, ?)')
            ->execute([$id, $byStaffId, $json]);
    }

    // ---- Changes made to one account, newest first, with the admin's name (by_name).
    // Each row also gets 'list' = the decoded changes.
    public static function forAccount($role, $id, $limit = 30)
    {
        $st = Database::get()->prepare(
            'SELECT c.*, s.name AS by_name FROM account_changes c JOIN staff s ON s.id = c.changed_by
             WHERE c.' . self::ownerColumn($role) . ' = ? ORDER BY c.id DESC LIMIT ' . (int)$limit
        );
        $st->execute([$id]);
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['list'] = json_decode($row['changes'], true) ?: [];
        }
        return $rows;
    }

    // ---- How many changes the person has not looked at yet (red number)
    public static function unseen($role, $id)
    {
        $st = Database::get()->prepare(
            'SELECT COUNT(*) FROM account_changes WHERE ' . self::ownerColumn($role) . ' = ? AND seen_at IS NULL'
        );
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    // ---- The person opened their notifications: everything counts as seen
    public static function markSeen($role, $id)
    {
        Database::get()
            ->prepare('UPDATE account_changes SET seen_at = NOW() WHERE ' . self::ownerColumn($role) . ' = ? AND seen_at IS NULL')
            ->execute([$id]);
    }
}
