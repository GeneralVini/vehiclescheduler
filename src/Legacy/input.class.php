<?php

/**
 * Legacy compatibility wrapper for input normalization.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerInput
{
    public static function int(
        array $source,
        string $key,
        int $default = 0,
        ?int $min = null,
        ?int $max = null
    ): int {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::int($source, $key, $default, $min, $max);
    }

    public static function string(
        array $source,
        string $key,
        int $maxLen = 255,
        string $default = ''
    ): string {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::string($source, $key, $maxLen, $default);
    }

    public static function text(
        array $source,
        string $key,
        int $maxLen = 65535,
        string $default = ''
    ): string {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::text($source, $key, $maxLen, $default);
    }

    public static function bool(array $source, string $key, bool $default = false): int
    {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::bool($source, $key, $default);
    }

    public static function enum(
        array $source,
        string $key,
        array $allowed,
        string $default = ''
    ): string {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::enum($source, $key, $allowed, $default);
    }

    public static function enumList(
        array $source,
        string $key,
        array $allowed,
        array $default = []
    ): array {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::enumList($source, $key, $allowed, $default);
    }

    public static function date(array $source, string $key, ?string $default = null): ?string
    {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::date($source, $key, $default);
    }

    public static function datetime(array $source, string $key, ?string $default = null): ?string
    {
        return \GlpiPlugin\Vehiclescheduler\Support\InputNormalizer::datetime($source, $key, $default);
    }
}
