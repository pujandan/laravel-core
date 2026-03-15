# AppSecure Locale Messages Guide

## Overview

All AppSecure exception messages are translatable using Laravel's localization system. You can override these messages in your project to customize them or translate them to different languages.

## Available Messages

All messages are defined in `languages/en/tsd_message.php` and `languages/id/tsd_message.php`:

| Key | English | Indonesian |
|-----|---------|------------|
| `secureKeyNotConfigured` | Encryption key not configured... | Kunci enkripsi tidak dikonfigurasi... |
| `secureKeyTooShort` | APP_KEY must be at least 32 characters... | APP_KEY harus minimal 32 karakter... |
| `secureEncryptionFailed` | Encryption failed. Please check openssl... | Enkripsi gagal. Silakan periksa openssl... |
| `secureDecryptionInvalidFormat` | Decryption failed. Invalid encrypted data format. | Dekripsi gagal. Format data terenkripsi tidak valid. |
| `secureDecryptionFailed` | Decryption failed. The data may be corrupted... | Dekripsi gagal. Data mungkin rusak... |

## How to Override Messages

### Option 1: Override in Your Project (Recommended)

Create `lang/en/tsd_message.php` in your Laravel project:

```php
<?php

return [
    'secureKeyNotConfigured' => 'Your custom message here',
    'secureKeyTooShort' => 'Your custom message for short key :length',
    // ... override only what you need
];
```

### Option 2: Create Additional Languages

Create `lang/es/tsd_message.php` for Spanish:

```php
<?php

return [
    'secureKeyNotConfigured' => 'Clave de encriptación no configurada...',
    'secureKeyTooShort' => 'APP_KEY debe tener al menos 32 caracteres...',
    'secureEncryptionFailed' => 'Encriptación fallida...',
    'secureDecryptionInvalidFormat' => 'Desencriptación fallida. Formato inválido.',
    'secureDecryptionFailed' => 'Desencriptación fallida. Los datos pueden estar corruptos...',
];
```

Then set locale: `app()->setLocale('es');`

### Option 3: Publish and Modify Package Files (Not Recommended)

You can publish the package language files to your project:

```bash
# If you add this feature to the service provider
php artisan vendor:publish --tag="laravel-tsd-lang"
```

Then modify the published files directly.

## Parameters

Some messages accept parameters:

- `secureKeyTooShort` - `:length` - The actual length of the key

Example usage:
```php
__('tsd_message.secureKeyTooShort', ['length' => 16])
// Output: "APP_KEY must be at least 32 characters long for AES-256 encryption. Current length: 16. Run: php artisan key:generate"
```

## Usage in AppSecure

The messages are automatically used by AppSecure when throwing exceptions:

```php
// In AppSecure::secretKey()
if (empty($key)) {
    throw new RuntimeException(__('tsd_message.secureKeyNotConfigured'));
}

// With parameter
if (strlen($key) < 32) {
    throw new RuntimeException(__('tsd_message.secureKeyTooShort', ['length' => strlen($key)]));
}
```

## Testing Locale Messages

```php
// Switch to English
app()->setLocale('en');
throw new RuntimeException(__('tsd_message.secureEncryptionFailed'));
// Output: "Encryption failed. Please check that the openssl extension is enabled."

// Switch to Indonesian
app()->setLocale('id');
throw new RuntimeException(__('tsd_message.secureEncryptionFailed'));
// Output: "Enkripsi gagal. Silakan periksa apakah ekstensi openssl telah diaktifkan."
```

## Benefits

1. **Flexibility**: Customize messages per project
2. **Multilingual**: Easy to add new languages
3. **User-Friendly**: Show appropriate messages to users in their language
4. **Maintainability**: Centralized message management