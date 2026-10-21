<?php
// app/models/FavouriteModel.php
// MODEL for the user's favourite services (the heart on a service card).
// Used by the home / service pages (filled hearts) and Profile -> প্রিয় সেবা (the list).

class FavouriteModel
{
    // ---- Ids of this user's favourites (to fill the hearts on the cards)
    public static function ids($userId)
    {
        $st = Database::get()->prepare('SELECT sub_service_id FROM favourites WHERE user_id = ?');
        $st->execute([$userId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    // ---- The favourites that can still be booked, newest heart first (same fields as the home page cards)
    public static function forUser($userId)
    {
        $st = Database::get()->prepare(
            'SELECT x.*, s.name_bn AS service_bn, s.name_en AS service_en, s.icon AS service_icon, s.color AS service_color
             FROM favourites f JOIN sub_services x ON x.id = f.sub_service_id JOIN services s ON s.id = x.service_id
             WHERE f.user_id = ? AND x.is_active = 1 AND s.is_active = 1 ORDER BY f.created_at DESC');
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    // ---- Heart on <-> off. Returns true when it is a favourite now.
    // Try to delete first: if a row was there it was on, so now it is off.
    public static function toggle($userId, $subId)
    {
        $db = Database::get();
        $delete = $db->prepare('DELETE FROM favourites WHERE user_id = ? AND sub_service_id = ?');
        $delete->execute([$userId, $subId]);
        if ($delete->rowCount()) {
            return false;
        }
        $db->prepare('INSERT IGNORE INTO favourites (user_id, sub_service_id) VALUES (?, ?)')->execute([$userId, $subId]);
        return true;
    }
}
