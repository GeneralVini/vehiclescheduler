<?php

namespace GlpiPlugin\Vehiclescheduler\Localization;

use Laminas\I18n\Translator\TextDomain;
use ReflectionProperty;
use Throwable;

final class LocaleManager
{
    public const DEFAULT_LOCALE = 'pt_BR';
    private const DOMAIN = 'vehiclescheduler';
    private const LEGACY_DOMAIN = 'plugin_vehiclescheduler';

    /**
     * @return array<string, array{label: string, native: string}>
     */
    public static function getSupportedLocales(): array
    {
        return [
            'pt_BR' => [
                'label'  => __('Portuguese', 'vehiclescheduler'),
                'native' => 'Português',
            ],
            'en_GB' => [
                'label'  => __('English', 'vehiclescheduler'),
                'native' => 'English',
            ],
            'es_ES' => [
                'label'  => __('Spanish', 'vehiclescheduler'),
                'native' => 'Español',
            ],
            'fr_FR' => [
                'label'  => __('French', 'vehiclescheduler'),
                'native' => 'Français',
            ],
        ];
    }

    public static function normalize(string $locale): string
    {
        return array_key_exists($locale, self::getSupportedLocales())
            ? $locale
            : self::DEFAULT_LOCALE;
    }

    public static function apply(string $locale): void
    {
        $normalized = self::normalize($locale);
        $_SESSION['glpilanguage'] = $normalized;
        \Session::loadLanguage($normalized);
        self::loadPluginPhpLocale($normalized);
        self::clearMenuCache();
    }

    /**
     * GLPI stores translated menu labels in the session. Rebuild it after
     * applying the plugin locale so breadcrumbs do not keep stale labels.
     */
    private static function clearMenuCache(): void
    {
        unset($_SESSION['glpimenu']);
    }

    /**
     * GLPI loads plugin gettext files by default. The project also ships PHP
     * maps generated from PO files; merge them after gettext so the selector
     * reflects the current locale even when MO files are stale.
     */
    private static function loadPluginPhpLocale(string $locale): void
    {
        global $TRANSLATE;

        $localeFile = dirname(__DIR__, 2) . '/locales/' . $locale . '.php';
        if (!is_file($localeFile) || !isset($TRANSLATE)) {
            return;
        }

        $LANG = [];

        try {
            include $localeFile;

            $messages = $LANG[self::LEGACY_DOMAIN] ?? null;
            if (!is_array($messages)) {
                return;
            }

            $loadedMessages = $TRANSLATE->getAllMessages(self::DOMAIN, $locale);
            if ($loadedMessages instanceof TextDomain) {
                $loadedMessages->merge(new TextDomain($messages));
                return;
            }

            $property = new ReflectionProperty($TRANSLATE, 'messages');
            $property->setAccessible(true);

            $translatorMessages = $property->getValue($TRANSLATE);
            $translatorMessages[self::DOMAIN][$locale] = new TextDomain($messages);
            $property->setValue($TRANSLATE, $translatorMessages);
        } catch (Throwable $exception) {
            // Keep GLPI gettext translations if the supplemental map cannot be loaded.
        }
    }
}
