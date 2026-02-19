<?php

declare(strict_types=1);

namespace Vibeable\Backend\Service;

use Vibeable\Backend\Database\DB;

final class AuthService
{
    private const JWT_TTL = 43200; // 12h in minutes

    /**
     * @return array{id: int, email: string, role: string}|null
     */
    public static function validateToken(string $jwt): ?array
    {
        $secret = getenv('JWT_SECRET') ?: '';
        if ($secret === '') {
            return null;
        }
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true), true);
        if (!is_array($payload) || !isset($payload['sub'], $payload['exp'])) {
            return null;
        }
        if ($payload['exp'] < time()) {
            return null;
        }
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true);
        if (!hash_equals(base64_encode($signature), strtr($parts[2], '-_', '+/'))) {
            return null;
        }
        $userId = (int) $payload['sub'];
        $row = DB::queryOne('SELECT id, email, role FROM users WHERE id = ?', [$userId]);
        return $row ?: null;
    }

    /**
     * @return array{token: string, user: array}
     */
    public static function login(string $email, string $password): array
    {
        $user = DB::queryOne('SELECT id, email, password_hash, role, name FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            throw new \Exception('Invalid credentials', 401);
        }
        unset($user['password_hash']);
        $token = self::issueToken((int) $user['id']);
        return ['token' => $token, 'user' => $user];
    }

    public static function issueToken(int $userId): string
    {
        $secret = getenv('JWT_SECRET') ?: getenv('APP_KEY') ?: 'dev-secret';
        $ttl = (int) (getenv('JWT_TTL') ?: self::JWT_TTL);
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + ($ttl * 60),
        ]);
        $b64Header = strtr(base64_encode($header), '+/', '-_');
        $b64Payload = strtr(base64_encode($payload), '+/', '-_');
        $signature = hash_hmac('sha256', $b64Header . '.' . $b64Payload, $secret, true);
        $b64Sig = strtr(base64_encode($signature), '+/', '-_');
        return $b64Header . '.' . $b64Payload . '.' . $b64Sig;
    }

    /**
     * @return array{id: int, email: string, name: string|null, role: string}
     */
    public static function getCurrentUser(): array
    {
        $user = $GLOBALS['__vibeable_user'] ?? null;
        if ($user === null) {
            throw new \Exception('Unauthorized', 401);
        }
        return $user;
    }
}
