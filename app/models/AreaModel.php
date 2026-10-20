<?php
// app/models/AreaModel.php
// MODEL = the data. Areas of Mymensingh (register page dropdown), from the "areas" table.
// users.area is a foreign key to areas.name, so only these can be saved.

class AreaModel
{
    private static $cache = null;

    public static function getAll()
    {
        if (self::$cache === null) {
            self::$cache = Database::get()->query('SELECT name FROM areas ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        }
        return self::$cache;
    }
}
