<?php
// app/models/ServiceModel.php
// MODEL = the data. For now the list is written here by hand.
// Later you can replace it with a database query (SELECT * FROM services).
// 'key' points to the name inside app/lang/bn.php and en.php.

class ServiceModel
{
    public static function getAll()
    {
        return [
            ['key' => 'service_cleaning',   'icon' => 'cleaning.svg',   'color' => 'mint'],
            ['key' => 'service_laundry',    'icon' => 'laundry.svg',    'color' => 'sky'],
            ['key' => 'service_electrical', 'icon' => 'electrical.svg', 'color' => 'sand'],
            ['key' => 'service_plumbing',   'icon' => 'plumbing.svg',   'color' => 'rose'],
            ['key' => 'service_ac',         'icon' => 'ac.svg',         'color' => 'lilac'],
        ];
    }
}
