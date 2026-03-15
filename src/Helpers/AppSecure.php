<?php

namespace Daniardev\LaravelTsd\Helpers;

use RuntimeException;

/**
 * Simple encryption/decryption helper for sensitive data.
 *
 * Uses AES-128-CBC encryption with Laravel's APP_KEY.
 * Suitable for encrypting small values like tokens, IDs, or simple data.
 *
 * WARNING: For production use with highly sensitive data,
 * consider using Laravel's built-in encryption features instead.
 *
 * @package Daniardev\LaravelTsd\Helpers
 */
class AppSecure
{
    /**
     * Get encryption key from Laravel's APP_KEY.
     *
     * Extracts the last 16 characters from APP_KEY for AES-128 encryption.
     *
     * @return string 16-character encryption key
     * @throws RuntimeException If APP_KEY is not configured
     */
    private static function secretKey(): string
    {
        $key = config('app.key');

        if (empty($key)) {
            throw new RuntimeException(
                'Encryption key not configured. Please set APP_KEY in your .env file ' .
                'or run: php artisan key:generate'
            );
        }

        // Use last 16 characters for AES-128
        return substr($key, -16);
    }

    /**
     * Encrypt a string value using AES-128-CBC encryption.
     *
     * The encrypted value is base64 encoded for safe storage/transmission.
     *
     * Usage:
     * ```php
     * $encrypted = AppSecure::encrypt('user_id_123');
     * // Returns: "U2FsdGVkX1..."
     * ```
     *
     * @param string $value The plain text value to encrypt
     * @return string Base64 encoded encrypted string
     * @throws RuntimeException If encryption fails
     */
    public static function encrypt(string $value): string
    {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16); // Zero IV for simplicity (NOT recommended for production)
        $method = 'aes-128-cbc';

        $encrypted = openssl_encrypt($value, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed. Please check that the openssl extension is enabled.');
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt a previously encrypted string value.
     *
     * Decodes the base64 input and decrypts using AES-128-CBC.
     *
     * Usage:
     * ```php
     * $decrypted = AppSecure::decrypt($encrypted);
     * // Returns: "user_id_123"
     * ```
     *
     * @param string $encoded Base64 encoded encrypted string
     * @return string The decrypted plain text value
     * @throws RuntimeException If decryption fails
     */
    public static function decrypt(string $encoded): string
    {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16); // Zero IV for simplicity (NOT recommended for production)
        $method = 'aes-128-cbc';

        $decoded = base64_decode($encoded);
        $decrypted = openssl_decrypt($decoded, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed. The data may be corrupted or encrypted with a different key.');
        }

        return $decrypted;
    }
}