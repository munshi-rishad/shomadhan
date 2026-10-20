<?php
// app/models/ProviderModel.php
// MODEL for service providers: the people who go to the customer and do the work (electrician, plumber ...).
// They don't log in. Each one belongs to one category (services.id) and can give many sub-services
// (sub_service_providers). Later, when a customer orders a sub-service, the admin picks one of its providers.
// Delete is soft (deleted_at), so old orders will keep their provider's name.

class ProviderModel
{
    const COLUMNS = ['service_id', 'name', 'mobile', 'age', 'gender', 'area', 'address', 'photo', 'note', 'status'];

    private static function one($sql, array $args)
    {
        $st = Database::get()->prepare($sql);
        $st->execute($args);
        return $st->fetch() ?: null;
    }

    private static function run($sql, array $args)
    {
        Database::get()->prepare($sql)->execute($args);
    }

    public static function find($id)
    {
        return self::one('SELECT * FROM providers WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function findDeleted($id)
    {
        return self::one('SELECT * FROM providers WHERE id = ? AND deleted_at IS NOT NULL', [$id]);
    }

    // another (not deleted) provider has this mobile?
    public static function mobileTaken($mobile, $id = 0)
    {
        return (bool)self::one('SELECT id FROM providers WHERE live_mobile = ? AND id <> ?', [$mobile, (int)$id]);
    }

    public static function save($id, array $d, $by)
    {
        $args = array_map(function ($c) use ($d) { return $d[$c]; }, self::COLUMNS);
        if ($id) {
            $args[] = $id;
            self::run('UPDATE providers SET ' . implode(' = ?, ', self::COLUMNS) . ' = ? WHERE id = ?', $args);
            if ((int)$d['service_id'] !== (int)($d['old_service_id'] ?? $d['service_id'])) {
                // moved to another category: the sub-services of the old category don't fit any more
                self::run('DELETE sp FROM sub_service_providers sp JOIN sub_services x ON x.id = sp.sub_service_id
                           WHERE sp.provider_id = ? AND x.service_id <> ?', [$id, $d['service_id']]);
            }
            return $id;
        }
        $args[] = $by;
        self::run('INSERT INTO providers (' . implode(', ', self::COLUMNS) . ', created_by) VALUES (' . rtrim(str_repeat('?, ', count(self::COLUMNS) + 1), ', ') . ')', $args);
        return (int)Database::get()->lastInsertId();
    }

    public static function delete($id, $by)
    {
        self::run('UPDATE providers SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL', [$by, $id]);
    }

    public static function restore($id)
    {
        self::run('UPDATE providers SET deleted_at = NULL, deleted_by = NULL WHERE id = ?', [$id]);
    }

    // all providers that can be picked (not deleted), for the sub-service form: [id, name, mobile, area, service_id, status, photo]
    public static function choices()
    {
        return Database::get()->query('SELECT id, name, mobile, area, service_id, status, photo FROM providers WHERE deleted_at IS NULL ORDER BY name')->fetchAll();
    }

    // ids of the providers who give this sub-service
    public static function idsFor($subServiceId)
    {
        $st = Database::get()->prepare('SELECT provider_id FROM sub_service_providers WHERE sub_service_id = ?');
        $st->execute([$subServiceId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    // names of the providers of each sub-service (admin sub-services list): [sub_service_id => ['RAHIM MIA', ...]]
    public static function namesBySub()
    {
        $out = [];
        $rows = Database::get()->query('SELECT sp.sub_service_id, p.name FROM sub_service_providers sp
                                        JOIN providers p ON p.id = sp.provider_id WHERE p.deleted_at IS NULL ORDER BY p.name');
        foreach ($rows as $r) {
            $out[(int)$r['sub_service_id']][] = $r['name'];
        }
        return $out;
    }

    // sub-services this provider gives (provider list / edit form)
    public static function subsOf($providerId)
    {
        $st = Database::get()->prepare('SELECT x.id, x.name_bn, x.name_en FROM sub_service_providers sp JOIN sub_services x ON x.id = sp.sub_service_id
                                        WHERE sp.provider_id = ? ORDER BY x.name_en');
        $st->execute([$providerId]);
        return $st->fetchAll();
    }

    // Saves who gives this sub-service. Only providers of the same category are kept.
    public static function setFor($subServiceId, $serviceId, array $ids)
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            self::run('DELETE FROM sub_service_providers WHERE sub_service_id = ?', [$subServiceId]);
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if ($ids) {
                $in = rtrim(str_repeat('?, ', count($ids)), ', ');
                self::run('INSERT INTO sub_service_providers (sub_service_id, provider_id)
                           SELECT ?, id FROM providers WHERE deleted_at IS NULL AND service_id = ? AND id IN (' . $in . ')',
                          array_merge([$subServiceId, $serviceId], $ids));
            }
            $db->commit();
        } catch (PDOException $ex) {
            $db->rollBack();
            throw $ex;
        }
    }
}
