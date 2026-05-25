<?php

namespace GlpiPlugin\Vehiclescheduler\Support;

final class InputNormalizer
{
    public static function int(
        array $source,
        string $key,
        int $default = 0,
        ?int $min = null,
        ?int $max = null
    ): int {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = filter_var($source[$key], FILTER_VALIDATE_INT);
        if ($value === false) {
            return $default;
        }

        if ($min !== null && $value < $min) {
            return $min;
        }

        if ($max !== null && $value > $max) {
            return $max;
        }

        return (int) $value;
    }

    public static function string(
        array $source,
        string $key,
        int $maxLen = 255,
        string $default = ''
    ): string {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string) $source[$key]);

        if ($maxLen > 0 && mb_strlen($value) > $maxLen) {
            $value = mb_substr($value, 0, $maxLen);
        }

        return $value;
    }

    public static function text(
        array $source,
        string $key,
        int $maxLen = 65535,
        string $default = ''
    ): string {
        return self::string($source, $key, $maxLen, $default);
    }

    public static function bool(array $source, string $key, bool $default = false): int
    {
        if (!array_key_exists($key, $source)) {
            return $default ? 1 : 0;
        }

        $value = $source[$key];

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value === 1 ? 1 : 0;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes', 'sim'], true) ? 1 : 0;
    }

    public static function enum(
        array $source,
        string $key,
        array $allowed,
        string $default = ''
    ): string {
        $value = self::string($source, $key, 255, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function enumList(
        array $source,
        string $key,
        array $allowed,
        array $default = []
    ): array {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $rawValues = $source[$key];
        if (!is_array($rawValues)) {
            $rawValues = [$rawValues];
        }

        $normalized = [];
        foreach ($rawValues as $value) {
            $item = trim((string) $value);
            if ($item === '' || !in_array($item, $allowed, true)) {
                continue;
            }

            $normalized[$item] = $item;
        }

        return array_values($normalized);
    }

    public static function date(array $source, string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string) $source[$key]);
        if ($value === '') {
            return $default;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date instanceof \DateTime || $date->format('Y-m-d') !== $value) {
            return $default;
        }

        return $value;
    }

    public static function datetime(array $source, string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $source)) {
            return $default;
        }

        $value = trim((string) $source[$key]);
        if ($value === '') {
            return $default;
        }

        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return $default;
    }
}
