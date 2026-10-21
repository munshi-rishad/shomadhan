<?php
// app/models/AreaModel.php
// MODEL for the areas of Mymensingh (the "areas" table). Used for the area dropdowns
// (register, profile, admin forms) and to check that a chosen area is real. The list is fixed (in schema.sql).
// users / staff / providers / bookings .area are foreign keys to areas.name (a rename follows everywhere).
// lat / lng = the area's middle point: used to show how far each provider is from a booking (no map on screen).

class AreaModel
{
    const NEAR_KM = 1.5;      // a provider this close to the booking's area is "near" (green group in the bookings page)

    // The list is read once per request and kept here (several forms on one page may ask for it)
    private static $cache = null;

    private static function db()
    {
        return Database::get();
    }

    // ---- All area names, A -> Z
    public static function getAll()
    {
        if (self::$cache === null) {
            self::$cache = self::db()->query('SELECT name FROM areas ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        }
        return self::$cache;
    }

    public static function byName($name)
    {
        $st = self::db()->prepare('SELECT * FROM areas WHERE name = ?');
        $st->execute([(string)$name]);
        return $st->fetch() ?: null;
    }

    // ---- Straight-line distance in km between two points (null when one point is missing)
    public static function km($lat1, $lng1, $lat2, $lng2)
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }
        $rad = M_PI / 180;
        $dLat = ($lat2 - $lat1) * $rad;
        $dLng = ($lng2 - $lng1) * $rad;
        $a = sin($dLat / 2) ** 2 + cos($lat1 * $rad) * cos($lat2 * $rad) * sin($dLng / 2) ** 2;
        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
