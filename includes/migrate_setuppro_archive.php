<?php
// Fixed, one-time content migration using only the application's own database.
function setuppro_migrate_public_archive($connection)
{
    if (DB_NAME !== 'sallesano_setuppro' || DB_USER !== 'setuppro_app') {
        throw new RuntimeException('Unexpected application database configuration.');
    }
    $identity = $connection->query('SELECT DATABASE() AS name')->fetch_assoc();
    if ($identity['name'] !== 'sallesano_setuppro') {
        throw new RuntimeException('Unexpected active database.');
    }
    $isDone = function () use ($connection) {
        $row = $connection->query("SELECT meta_value FROM system_info WHERE meta_field = 'setuppro_public_archive_v1' LIMIT 1")->fetch_assoc();
        return $row && $row['meta_value'] === 'done';
    };
    if ($isDone()) {
        return false;
    }
    $lock = $connection->query("SELECT GET_LOCK('sallesano_setuppro_public_archive_v1', 5) AS acquired")->fetch_assoc();
    if ((int) $lock['acquired'] !== 1) {
        throw new RuntimeException('Content migration is already running.');
    }
    try {
        if ($isDone()) {
            return false;
        }
        $sql = file_get_contents(dirname(__DIR__) . '/database/mirror-public-content.sql');
        if ($sql === false) {
            throw new RuntimeException('Missing fixed content migration.');
        }
        $marker = "INSERT INTO system_info (meta_field, meta_value) VALUES ('setuppro_public_archive_v1', 'done') ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value);\nCOMMIT;";
        $sql = str_replace('COMMIT;', $marker, $sql);
        if (!$connection->multi_query($sql)) {
            throw new RuntimeException('Content migration failed.');
        }
        do {
            if ($result = $connection->store_result()) {
                $result->free();
            }
            if (!$connection->more_results()) {
                break;
            }
            if (!$connection->next_result()) {
                throw new RuntimeException('Content migration statement failed.');
            }
        } while (true);
        return true;
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    } finally {
        $connection->query("SELECT RELEASE_LOCK('sallesano_setuppro_public_archive_v1')");
    }
}
