<?php

namespace Daniardev\LaravelTsd\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * General Helper Class
 *
 * Contains generic utility methods that can be used across projects.
 */
class AppHelper
{
    /**
     * Replace spaces with hyphens
     */
    public static function replaceSpace(string $data): string
    {
        return str_replace(' ', '-', $data);
    }

    /**
     * Convert string to alphanumeric only
     */
    public static function toAz09(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    /**
     * Format currency for display
     */
    public static function formatCurrency(
        ?float $amount,
        string $currency = 'IDR',
        int $decimals = 0,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.'
    ): string {
        if ($amount === null) {
            return '0';
        }

        $formatted = number_format(
            $amount,
            $decimals,
            $decimalSeparator,
            $thousandsSeparator
        );

        return $currency . ' ' . $formatted;
    }

    /**
     * Calculate arrival date
     */
    public static function arrivalDate(?string $departureDate, ?int $seat): ?string
    {
        if ($departureDate === null) {
            return null;
        }

        $departure = Carbon::parse($departureDate);

        if ($seat !== null) {
            $departure->addDays($seat);
        }

        return $departure->format('Y-m-d');
    }

    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'd F Y'): string
    {
        if ($date === null) {
            return '-';
        }

        return Carbon::parse($date)->translatedFormat($format);
    }

    /**
     * Convert array keys to snake_case
     */
    public static function toSnakeCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = Str::snake($key);

            if (is_array($value)) {
                $result[$newKey] = self::toSnakeCase($value);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Convert array keys to camelCase
     */
    public static function toCamelCase(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = Str::camel($key);

            if (is_array($value)) {
                $result[$newKey] = self::toCamelCase($value);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Check if string is in camelCase format
     */
    public static function isCamel(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return preg_match('/^[a-z][a-zA-Z0-9]*$/', $value) === 1;
    }

    /**
     * Convert value to boolean
     */
    public static function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * Merge multiple arrays
     */
    public static function arrayMerge(...$arrays): array
    {
        $result = [];
        foreach ($arrays as $array) {
            $result = array_merge($result, $array);
        }
        return $result;
    }

    /**
     * Get storage asset URL
     */
    public static function assetStorage(?string $image): ?string
    {
        if ($image === null) {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return config('app.url') . '/storage/' . $image;
    }

    /**
     * Return data or replace if null
     */
    public static function ifNull($data, $replace = null)
    {
        return $data ?? $replace;
    }

    /**
     * Get class name of object
     */
    public static function getClass(object $object): string
    {
        return get_class($object);
    }

    /**
     * Get short class name (without namespace)
     */
    public static function getClassName(object $object): string
    {
        return class_basename($object);
    }

    /**
     * Convert DateTime to string with microseconds
     */
    public static function withMicro(Carbon|string|null $date = null): string
    {
        if ($date === null) {
            return Carbon::now()->format('Y-m-d H:i:s.u');
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format('Y-m-d H:i:s.u');
    }

    /**
     * Convert enum cases to string
     */
    public static function enumCasesToString(string $enumClass, string $separator = ','): string
    {
        if (!enum_exists($enumClass)) {
            return '';
        }

        $cases = $enumClass::cases();
        $values = array_map(fn($case) => $case->value ?? $case->name, $cases);

        return implode($separator, $values);
    }

    /**
     * Convert base64 to image path
     */
    public static function base64Image(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Generate QR code URL
     */
    public static function generateQrCode(string $data): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
    }
}