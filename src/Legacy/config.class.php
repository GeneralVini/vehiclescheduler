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
        global $DB;
        
        // Try to get user-specific locale first
        if (Session::getLoginUserID()) {
            $result = $DB->request([
                'FROM' => self::getTable(),
                'WHERE' => ['users_id' => Session::getLoginUserID()]
            ])->current();
            
            if ($result && !empty($result['plugin_locale'])) {
                $locale = $result['plugin_locale'];
                return \GlpiPlugin\Vehiclescheduler\Localization\LocaleManager::normalize($locale);
            }
        }
        
        // Fall back to global setting
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

        // If user is logged in, save per-user setting
        if (Session::getLoginUserID()) {
            global $DB;
            $user_id = Session::getLoginUserID();
            
            $existing = $DB->request([
                'FROM' => self::getTable(),
                'WHERE' => ['users_id' => $user_id]
            ])->current();

            if ($existing) {
                return (bool) $DB->update(self::getTable(), [
                    'plugin_locale' => $locale
                ], [
                    'users_id' => $user_id
                ]);
            } else {
                return (bool) $DB->insert(self::getTable(), [
                    'users_id' => $user_id,
                    'plugin_locale' => $locale
                ]);
            }
        }

        // Fall back to global setting
        return self::setString('plugin_locale', $locale);
    }
}
