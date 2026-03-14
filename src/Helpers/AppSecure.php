<?php

namespace Daniardev\LaravelTsd\Helpers;

use RuntimeException;

class AppSecure
{
    /**
     * Get secret key from config.
     *
     * @return string
     * @throws RuntimeException
     */
    private static function secretKey(): string
    {
        $key = config('app.key');

        if (!$key) {
            throw new RuntimeException('Encryption key not configured. Please set APP_KEY in your .env file.');
        }

        return substr($key, -16);
    }

    /**
     * Encrypt value using AES-128-CBC.
     *
     * @param string $value Value to encrypt
     * @return string Base64 encoded encrypted value
     * @throws RuntimeException
     */
    public static function encrypt(string $value): string
    {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16);
        $method = 'aes-128-cbc';
        $encryptedString = openssl_encrypt($value, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($encryptedString === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($encryptedString);
    }

    /**
     * Decrypt value using AES-128-CBC.
     *
     * @param string $encoded Base64 encoded encrypted value
     * @return string Decrypted value
     * @throws RuntimeException
     */
    public static function decrypt(string $encoded): string
    {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16);
        $method = 'aes-128-cbc';
        $base64 = base64_decode($encoded);
        $decrypted = openssl_decrypt($base64, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $decrypted;
    }
}
