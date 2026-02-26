<?php

namespace DaniarDev\LaravelCore\Helpers;


class AppSecure
{
    static private function secretKey() : string{
        $key = config('app.key', 'Ho);fYI2t^ylEx%f');
        return substr($key, -16);
    }

    static public function encrypt(string $value) : string {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16);
        $method = 'aes-128-cbc';
        $encryptedString = openssl_encrypt($value, $method, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encryptedString);
    }

    static public function decrypt($encoded) : string {
        $key = self::secretKey();
        $iv = str_repeat("\0", 16);
        $method = 'aes-128-cbc';
        $base64 = base64_decode($encoded);
        return openssl_decrypt($base64, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}
