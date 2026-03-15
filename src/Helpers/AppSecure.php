<?php

namespace Daniardev\LaravelTsd\Helpers;

use RuntimeException;

/**
 * Secure encryption/decryption helper compatible with Flutter/Dart.
 *
 * Uses AES-256-CBC encryption with random IV for production-grade security.
 * The IV is stored alongside the ciphertext so Flutter/Dart can decrypt using the same key.
 *
 * Format: [BASE64(IV + CIPHERTEXT)]
 * - IV: 16 bytes (random)
 * - Ciphertext: variable length
 * - Combined, then base64 encoded
 *
 * Flutter/Dart Implementation:
 * ```dart
 * import 'package:cryptography/cryptography.dart';
 * import 'package:crypto/crypto.dart';
 *
 * String decrypt(String encrypted, String key) {
 *   final keyBytes = Key.fromUtf8(key.substring(0, 32));
 *   final decoded = base64.decode(encrypted);
 *   final iv = decoded.sublist(0, 16);
 *   final cipher = decoded.sublist(16);
 *
 *   final decryptor = Decryptor(Aes.cbc, keyBytes, iv);
 *   return String.fromCharCodes(decryptor.decrypt(cipher));
 * }
 * ```
 *
 * @package Daniardev\LaravelTsd\Helpers
 */
class AppSecure
{
    /**
     * Get encryption key from Laravel's APP_KEY.
     *
     * Uses first 32 characters of APP_KEY for AES-256 encryption.
     * For cross-platform compatibility, ensure Flutter uses the same key.
     *
     * @return string 32-character encryption key for AES-256
     * @throws RuntimeException If APP_KEY is not configured or too short
     */
    private static function secretKey(): string
    {
        $key = config('app.key');

        if (empty($key)) {
            throw new RuntimeException(__('tsd_message.secureKeyNotConfigured'));
        }

        // Remove 'base64:' prefix if present
        if (str_starts_with($key, 'base64:')) {
            $key = substr($key, 7);
        }

        // Use first 32 characters for AES-256
        if (strlen($key) < 32) {
            throw new RuntimeException(__('tsd_message.secureKeyTooShort', ['length' => strlen($key)]));
        }

        return substr($key, 0, 32);
    }

    /**
     * Encrypt a string value using AES-256-CBC with random IV.
     *
     * The IV is prepended to the ciphertext for cross-platform compatibility.
     * Format: [IV (16 bytes) + CIPHERTEXT] -> BASE64
     *
     * This format allows Flutter/Dart to decrypt using the same key:
     * 1. Base64 decode to get IV + ciphertext
     * 2. Extract first 16 bytes as IV
     * 3. Decrypt the rest using AES-256-CBC
     *
     * Usage:
     * ```php
     * $encrypted = AppSecure::encrypt('user_id_123');
     * // Returns: "YWJjZGVmZ2hpams..." (different each time due to random IV)
     *
     * $decrypted = AppSecure::decrypt($encrypted);
     * // Returns: "user_id_123"
     * ```
     *
     * @param string $value The plain text value to encrypt
     * @return string Base64 encoded (IV + ciphertext) for cross-platform compatibility
     * @throws RuntimeException If encryption fails
     */
    public static function encrypt(string $value): string
    {
        $key = self::secretKey();
        $ivLength = 16;
        $iv = openssl_random_pseudo_bytes($ivLength);
        $method = 'aes-256-cbc';

        // Encrypt with random IV
        $encrypted = openssl_encrypt($value, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException(__('tsd_message.secureEncryptionFailed'));
        }

        // Combine IV + ciphertext, then base64 encode
        // Format: BASE64(IV . CIPHERTEXT)
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a previously encrypted string value.
     *
     * Extracts the IV from the beginning of the ciphertext, then decrypts.
     * Compatible with Flutter/Dart implementations using the same key.
     *
     * Usage:
     * ```php
     * $decrypted = AppSecure::decrypt($encrypted);
     * // Returns: "user_id_123"
     * ```
     *
     * @param string $encrypted Base64 encoded (IV + ciphertext) from encrypt()
     * @return string The decrypted plain text value
     * @throws RuntimeException If decryption fails
     */
    public static function decrypt(string $encrypted): string
    {
        $key = self::secretKey();
        $ivLength = 16;
        $method = 'aes-256-cbc';

        // Base64 decode
        $decoded = base64_decode($encrypted);

        if ($decoded === false || strlen($decoded) < $ivLength) {
            throw new RuntimeException(__('tsd_message.secureDecryptionInvalidFormat'));
        }

        // Extract IV (first 16 bytes)
        $iv = substr($decoded, 0, $ivLength);

        // Extract ciphertext (after IV)
        $ciphertext = substr($decoded, $ivLength);

        // Decrypt
        $decrypted = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException(__('tsd_message.secureDecryptionFailed'));
        }

        return $decrypted;
    }

    /**
     * Get the raw encryption key for external use.
     *
     * Use this to get the key for Flutter/Dart configuration.
     * Returns the key in a format suitable for cross-platform usage.
     *
     * Usage:
     * ```php
     * // Get key for Flutter configuration
     * $key = AppSecure::getKeyForFlutter();
     * // Store in Flutter .env: ENCRYPTION_KEY=your_key_here
     * ```
     *
     * @return string The encryption key (first 32 chars of APP_KEY)
     * @throws RuntimeException If APP_KEY is not configured
     */
    public static function getKeyForFlutter(): string
    {
        return self::secretKey();
    }
}