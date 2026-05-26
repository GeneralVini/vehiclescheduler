<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerConfig extends CommonDBTM
{
    public static function getTable($classname = null): string
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::TABLE;
    }

    public static function ensureTable(): bool
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::ensureTable();
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::getBool($key, $default);
    }

    public static function setBool(string $key, bool $value): bool
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::setBool($key, $value);
    }

    public static function getString(string $key, string $default = ''): string
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::getString($key, $default);
    }

    public static function setString(string $key, string $value): bool
    {
        return \GlpiPlugin\Vehiclescheduler\Config\PluginConfigStore::setString($key, $value);
    }

    public static function shouldAutoOpenDepartureChecklistAfterApproval(): bool
    {
        return self::getBool('auto_departure_checklist_after_approval', true);
    }

    /**
     * @return array<string, array{label: string, native: string}>
     */
    public static function getSupportedLocales(): array
    {
        return \GlpiPlugin\Vehiclescheduler\Localization\LocaleManager::getSupportedLocales();
    }

    public static function getPluginLocale(): string
    {
        $locale = self::getString(
            'plugin_locale',
            \GlpiPlugin\Vehiclescheduler\Localization\LocaleManager::DEFAULT_LOCALE
        );

        return \GlpiPlugin\Vehiclescheduler\Localization\LocaleManager::normalize($locale);
    }

    public static function setPluginLocale(string $locale): bool
    {
        if (!array_key_exists($locale, self::getSupportedLocales())) {
            return false;
        }

        return self::setString('plugin_locale', $locale);
    }
}
