<?php

namespace GlpiPlugin\Vehiclescheduler\Localization;

final class LocaleManager
{
    public const DEFAULT_LOCALE = 'pt_BR';

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
    }
}
