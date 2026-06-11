<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Support;

/**
 * Small, defensive helpers for reading values out of decoded API payloads.
 *
 * API responses are not always perfectly consistent (nullable fields, the odd
 * camelCase vs snake_case key), so DTOs read through these helpers rather than
 * assuming a key exists or has a given type.
 */
final class Arr
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function string(array $data, string $key, ?string $default = null): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null || is_array($value)) {
            return $default;
        }

        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    /**
     * Read the first present key from a list of candidates (handles key drift
     * such as `shipping_address` vs `shippingAddress`).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    public static function stringFrom(array $data, array $keys, ?string $default = null): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                return self::string($data, $key, $default);
            }
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function int(array $data, string $key, ?int $default = null): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function float(array $data, string $key, ?float $default = null): ?float
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        return $value === null ? $default : (bool) $value;
    }

    /**
     * Read a nested associative array, checking several candidate keys.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public static function arrayFrom(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                /** @var array<string, mixed> */
                return $data[$key];
            }
        }

        return [];
    }
}
