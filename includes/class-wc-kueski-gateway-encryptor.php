<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Kueski_Gateway_Encryptor
{
    const ENCRYPTION_METHOD = 'AES-256-ECB';

    static public function encryptData($data, $key)
    {
        $hash = hash('sha256', $key, true);
        $encrypted = openssl_encrypt(
                        $data,
                        self::ENCRYPTION_METHOD,
                        $hash,
                        OPENSSL_RAW_DATA
                    );
        $encoded = urlencode(base64_encode($encrypted));

        return $encoded;
    }

    static public function decryptData($data, $key)
    {
        $hash = hash('sha256', $key, true);
        $decoded = urldecode($data);
        $decoded = base64_decode($data);
        $decrypted = openssl_decrypt(
                        $decoded,
                        self::ENCRYPTION_METHOD,
                        $hash,
                        OPENSSL_RAW_DATA
                    );

        return $decrypted;
    }
}