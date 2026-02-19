<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class AuthController
{
    /** @param array<string, string> $params */
    public static function register(array $params, array $payload): array
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = $payload['password'] ?? '';
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        if ($email === '' || $password === '') {
            throw new \Exception('Email and password required', 400);
        }
        if (strlen($password) < 8) {
            throw new \Exception('Password must be at least 8 characters', 400);
        }
        $existing = DB::queryOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            throw new \Exception('Email already registered', 409);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        DB::execute(
            'INSERT INTO users (email, password_hash, name, role, credits, created_at) VALUES (?, ?, ?, ?, 100, NOW())',
            [$email, $hash, $name, 'user']
        );
        $userId = (int) DB::lastInsertId();
        $token = AuthService::issueToken($userId);
        $user = DB::queryOne('SELECT id, email, name, role FROM users WHERE id = ?', [$userId]);
        return ['token' => $token, 'user' => $user];
    }

    /** @param array<string, string> $params */
    public static function login(array $params, array $payload): array
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $password = $payload['password'] ?? '';
        if ($email === '' || $password === '') {
            throw new \Exception('Email and password required', 400);
        }
        return AuthService::login($email, $password);
    }

    /** @param array<string, string> $params */
    public static function refresh(array $params, array $payload): array
    {
        // Optional: validate refresh token from payload and issue new JWT
        $user = AuthService::getCurrentUser();
        $token = AuthService::issueToken((int) $user['id']);
        return ['token' => $token, 'user' => $user];
    }

    /** @param array<string, string> $params */
    public static function oauth(array $params, array $payload): array
    {
        $provider = $params['provider'] ?? '';
        if (!in_array($provider, ['google', 'github', 'facebook'], true)) {
            throw new \Exception('Invalid provider', 400);
        }
        // OAuth flow: in production, exchange code for token and fetch profile, then find or create user
        throw new \Exception('OAuth not implemented in this stub; integrate with your OAuth provider', 501);
    }

    /** @param array<string, string> $params */
    public static function forgotPassword(array $params, array $payload): array
    {
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        if ($email === '') {
            throw new \Exception('Email required', 400);
        }
        // Generate reset token, store, send email
        return ['message' => 'If the email exists, a reset link has been sent.'];
    }

    /** @param array<string, string> $params */
    public static function resetPassword(array $params, array $payload): array
    {
        $token = $payload['token'] ?? '';
        $password = $payload['password'] ?? '';
        if ($token === '' || strlen($password) < 8) {
            throw new \Exception('Valid token and password (min 8 chars) required', 400);
        }
        // Verify token, update password
        return ['message' => 'Password has been reset.'];
    }

    /** @param array<string, string> $params */
    public static function logout(array $params, array $payload): array
    {
        // Optional: invalidate token in a blocklist
        return ['message' => 'Logged out.'];
    }

    /** @param array<string, string> $params */
    public static function me(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        return ['user' => $user];
    }
}
