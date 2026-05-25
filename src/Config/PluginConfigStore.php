<?php

namespace GlpiPlugin\Vehiclescheduler\Config;

final class PluginConfigStore
{
    public const TABLE = 'glpi_plugin_vehiclescheduler_configs';

    public static function ensureTable(): bool
    {
        global $DB;

        if ($DB->tableExists(self::TABLE)) {
            return true;
        }

        $defaultCharset = \DBConnection::getDefaultCharset();
        $defaultCollation = \DBConnection::getDefaultCollation();

        return (bool) $DB->doQuery("
            CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `config_key` varchar(100) NOT NULL,
                `config_value` varchar(255) NOT NULL DEFAULT '',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$defaultCharset} COLLATE={$defaultCollation}
        ");
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::getString($key, $default ? '1' : '0');

        return (int) $value === 1;
    }

    public static function setBool(string $key, bool $value): bool
    {
        return self::setString($key, $value ? '1' : '0');
    }

    public static function getString(string $key, string $default = ''): string
    {
        global $DB;

        if (!self::ensureTable()) {
            return $default;
        }

        $row = $DB->request([
            'FROM'  => self::TABLE,
            'WHERE' => ['config_key' => $key],
        ])->current();

        if (!$row) {
            return $default;
        }

        return (string) ($row['config_value'] ?? $default);
    }

    public static function setString(string $key, string $value): bool
    {
        global $DB;

        if (!self::ensureTable()) {
            return false;
        }

        $exists = $DB->request([
            'FROM'  => self::TABLE,
            'WHERE' => ['config_key' => $key],
        ])->current();

        $payload = [
            'config_key'   => $key,
            'config_value' => $value,
            'date_mod'     => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            return (bool) $DB->update(self::TABLE, $payload, ['config_key' => $key]);
        }

        $payload['date_creation'] = date('Y-m-d H:i:s');

        return (bool) $DB->insert(self::TABLE, $payload);
    }
}
