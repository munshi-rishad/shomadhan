<?php
// app/models/AreaModel.php
// MODEL for the areas of Mymensingh (the "areas" table). Used for the area dropdowns
// (register, profile, admin forms) and to check that a chosen area is real.
// users.area is a foreign key to areas.name, so only these can be saved.

class AreaModel
{
    // The list is read once per request and kept here (several forms on one page may ask for it)
    private static $cache = null;

    // ---- All area names, A -> Z
    public static function getAll()
    {
        if (self::$cache === null) {
            self::$cache = Database::get()->query('SELECT name FROM areas ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        }
        return self::$cache;
    }
}
