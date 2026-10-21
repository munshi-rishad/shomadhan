<?php
// app/models/ProviderModel.php
// MODEL for service providers: the people who go to the customer and do the work (electrician, plumber ...).
// They don't log in. Each one belongs to one category (services.id) and can give many sub-services
// (sub_service_providers). When a customer books a sub-service, the office picks one of its providers.
// Delete is soft (deleted_at), so old bookings keep their provider's name.
// Used by the admin panel (providers page, sub-service form).

class ProviderModel
{
    // Columns the admin form saves (same order for INSERT and UPDATE)
    const COLUMNS = ['service_id', 'name', 'mobile', 'email', 'age', 'gender', 'area', 'address', 'photo', 'note', 'status'];

    // ---- Small database helpers

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

    // "?, ?, ?" for $count values
    private static function placeholders($count)
    {
        return rtrim(str_repeat('?, ', $count), ', ');
    }

    // ---- Find

    public static function find($id)
    {
        return self::one('SELECT * FROM providers WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    // A deleted provider (for the "restore" button), or null
    public static function findDeleted($id)
    {
        return self::one('SELECT * FROM providers WHERE id = ? AND deleted_at IS NOT NULL', [$id]);
    }

    // ---- Duplicate checks: another (not deleted) provider already has this mobile / email?
    // $id = the provider being edited (skips itself)

    public static function mobileTaken($mobile, $id = 0)
    {
        return (bool)self::one('SELECT id FROM providers WHERE live_mobile = ? AND id <> ?', [$mobile, (int)$id]);
    }

    public static function emailTaken($email, $id = 0)
    {
        if ($email === null || $email === '') {
            return false;
        }
        return (bool)self::one('SELECT id FROM providers WHERE live_email = ? AND id <> ?', [strtolower($email), (int)$id]);
    }

    // ---- Add or edit. $id = 0 adds a new one (created_by = $by). Returns the provider's id.
    public static function save($id, array $d, $by)
    {
        $args = array_map(function ($col) use ($d) { return $d[$col]; }, self::COLUMNS);

        if ($id) {
            $args[] = $id;
            self::run('UPDATE providers SET ' . implode(' = ?, ', self::COLUMNS) . ' = ? WHERE id = ?', $args);

            // Moved to another category: the sub-services of the old category don't fit any more
            if ((int)$d['service_id'] !== (int)($d['old_service_id'] ?? $d['service_id'])) {
                self::run('DELETE sp FROM sub_service_providers sp JOIN sub_services x ON x.id = sp.sub_service_id
                           WHERE sp.provider_id = ? AND x.service_id <> ?', [$id, $d['service_id']]);
            }
            return $id;
        }

        $args[] = $by;
        self::run('INSERT INTO providers (' . implode(', ', self::COLUMNS) . ', created_by)'
                . ' VALUES (' . self::placeholders(count(self::COLUMNS) + 1) . ')', $args);
        return (int)Database::get()->lastInsertId();
    }

    // ---- Soft delete / restore

    public static function delete($id, $by)
    {
        self::run('UPDATE providers SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL', [$by, $id]);
    }

    public static function restore($id)
    {
        self::run('UPDATE providers SET deleted_at = NULL, deleted_by = NULL WHERE id = ?', [$id]);
    }

    // ---- Providers <-> sub-services

    // All providers that can be picked (not deleted), for the sub-service form
    public static function choices()
    {
        return Database::get()
            ->query('SELECT id, name, mobile, area, service_id, status, photo FROM providers WHERE deleted_at IS NULL ORDER BY id')
            ->fetchAll();
    }

    // Ids of the providers who give this sub-service
    public static function idsFor($subServiceId)
    {
        $st = Database::get()->prepare('SELECT provider_id FROM sub_service_providers WHERE sub_service_id = ?');
        $st->execute([$subServiceId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    // Names of the providers of each sub-service (admin sub-services list): [sub_service_id => ['RAHIM MIA', ...]]
    public static function namesBySub()
    {
        $names = [];
        $rows = Database::get()->query('SELECT sp.sub_service_id, p.name FROM sub_service_providers sp
                                        JOIN providers p ON p.id = sp.provider_id WHERE p.deleted_at IS NULL ORDER BY p.id');
        foreach ($rows as $row) {
            $names[(int)$row['sub_service_id']][] = $row['name'];
        }
        return $names;
    }

    // Saves who gives this sub-service (replaces the old list). Only live providers of the same category are kept.
    public static function setFor($subServiceId, $serviceId, array $ids)
    {
        $db = Database::get();
        $db->beginTransaction();
        try {
            self::run('DELETE FROM sub_service_providers WHERE sub_service_id = ?', [$subServiceId]);
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if ($ids) {
                self::run('INSERT INTO sub_service_providers (sub_service_id, provider_id)
                           SELECT ?, id FROM providers WHERE deleted_at IS NULL AND service_id = ? AND id IN (' . self::placeholders(count($ids)) . ')',
                          array_merge([$subServiceId, $serviceId], $ids));
            }
            $db->commit();
        } catch (PDOException $ex) {
            $db->rollBack();
            throw $ex;
        }
    }
}
