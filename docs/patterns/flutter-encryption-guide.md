# Flutter/Dart Implementation Guide

This guide explains how to implement encryption/decryption in Flutter/Dart that is compatible with `AppSecure` from Laravel TSD package.

## Setup

### 1. Get the Encryption Key

First, get the key from your Laravel application:

```bash
# In your Laravel project
php artisan tinker
>>> Daniardev\LaravelTsd\Helpers\AppSecure::getKeyForFlutter();
# Copy the output (32 characters)
```

Or add to your Flutter `.env` file:

```env
# Copy from Laravel's APP_KEY (first 32 chars)
ENCRYPTION_KEY=your_32_character_app_key_here
```

### 2. Add Dependencies

Add to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  encrypt: ^5.0.0
  http: ^1.0.0
```

Then run:

```bash
flutter pub get
```

## Implementation

### Recommended Version (Singleton Pattern)

For production use with proper initialization and error handling:

```dart
import 'dart:convert';
import 'dart:typed_data';
import 'package:encrypt/encrypt.dart';

class AppSecure {
  static AppSecure? _instance;
  late final String _secretKey;

  AppSecure._(String secretKey) : _secretKey = secretKey;

  /// Initialize once at app startup
  static void init(String secretKey) {
    if (_instance != null) {
      throw StateError('AppSecure already initialized.');
    }
    if (secretKey.length < 32) {
      throw ArgumentError('Secret key must be at least 32 characters');
    }
    _instance = AppSecure._(secretKey.substring(0, 32));
  }

  static AppSecure get instance {
    if (_instance == null) {
      throw StateError('AppSecure not initialized. Call init() first.');
    }
    return _instance!;
  }

  String encrypt(String value) {
    final iv = IV.fromSecureRandom(16); // RANDOM IV - SECURE!
    final key = Key.fromUtf8(_secretKey);
    final encrypter = Encrypter(AES(key, mode: AESMode.cbc));
    final encrypted = encrypter.encrypt(value, iv: iv);

    // Combine IV + ciphertext
    final ivBytes = iv.bytes;
    final encryptedBytes = encrypted.bytes;
    final combined = Uint8List(ivBytes.length + encryptedBytes.length);
    combined.setAll(0, ivBytes);
    combined.setAll(ivBytes.length, encryptedBytes);

    return base64Encode(combined);
  }

  String decrypt(String encoded) {
    final decoded = base64Decode(encoded);
    if (decoded.length < 16) {
      throw ArgumentError('Invalid encrypted data format');
    }

    final iv = IV(decoded.sublist(0, 16));
    final cipherText = decoded.sublist(16);

    final key = Key.fromUtf8(_secretKey);
    final encrypter = Encrypter(AES(key, mode: AESMode.cbc));

    return encrypter.decrypt(Encrypted(cipherText), iv: iv);
  }
}
```

**Usage:**
```dart
// In main()
AppSecure.init('your_32_byte_secret_key_here');

// Encrypt
final encrypted = AppSecure.instance.encrypt('sensitive data');

// Decrypt
final decrypted = AppSecure.instance.decrypt(encrypted);
```

## Usage Example

```dart
// Encrypt user ID
final userId = '12345';
final encrypted = AppSecure.encrypt(userId);
print('Encrypted: $encrypted');

// Decrypt user ID
final decrypted = AppSecure.decrypt(encrypted);
print('Decrypted: $decrypted'); // Output: 12345
```

## API Integration Example

```dart
class ApiService {
  final String baseUrl;

  ApiService({required this.baseUrl});

  Future<Map<String, dynamic>> getUser(String encryptedId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/users/$encryptedId'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to load user');
    }
  }

  // Usage
  Future<void> loadUser() async {
    try {
      final userId = AppSecure.encrypt('12345');
      final data = await getUser(userId);

      // Decrypt if needed
      final decryptedId = AppSecure.decrypt(data['user']['id']);
      print('User ID: $decryptedId');
    } catch (e) {
      print('Error: $e');
    }
  }
}
```

## Testing Encryption/Decryption

```dart
void testEncryption() {
  // Original value
  const original = 'user_id_123';
  print('Original: $original');

  // Encrypt
  final encrypted = AppSecure.encrypt(original);
  print('Encrypted: $encrypted');

  // Decrypt
  final decrypted = AppSecure.decrypt(encrypted);
  print('Decrypted: $decrypted');

  // Verify
  assert(original == decrypted, 'Encryption/Decryption failed!');
  print('✅ Encryption/Decryption works correctly!');
}
```

## Important Notes

1. **Key Matching**: Ensure Flutter uses the EXACT SAME key as Laravel (first 32 characters of APP_KEY)

2. **APP_KEY Format**: Laravel's APP_KEY may have `base64:` prefix. Use `AppSecure::getKeyForFlutter()` to get the raw key.

3. **AES-256-CBC**: Both Laravel and Flutter use the same algorithm (AES-256-CBC with 32-char key)

4. **Random IV**: Each encryption produces different output (due to random IV), but decryption always works

5. **Format**: `BASE64(IV + CIPHERTEXT)` - IV is 16 bytes, prepended to ciphertext

6. **Security**: This implementation is production-grade with random IV generation

7. **Cross-Platform**: PHP encrypted data can be decrypted by Flutter, and vice versa