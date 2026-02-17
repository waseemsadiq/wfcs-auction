<?php
declare(strict_types=1);

namespace Core;

class JWT
{
    /**
     * Encode a payload as a signed JWT (HS256).
     */
    public static function encode(array $payload, string $secret): string
    {
        $header  = self::base64UrlEncode((string)json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = $payload['iat'] ?? time();
        $body    = self::base64UrlEncode((string)json_encode($payload));
        $sig     = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", $secret, true));
        return "$header.$body.$sig";
    }

    /**
     * Decode and verify a JWT. Returns the payload array or null on failure.
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $sig] = $parts;

        $expected = self::base64UrlEncode(hash_hmac('sha256', "$header.$payload", $secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!is_array($data)) {
            return null;
        }

        // Check expiry
        if (isset($data['exp']) && $data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
