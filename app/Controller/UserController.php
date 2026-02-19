<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class UserController
{
    /** @param array<string, string> $params */
    public static function show(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $row = DB::queryOne(
            'SELECT id, email, name, role, locale, created_at, subscription_plan_id FROM users WHERE id = ?',
            [$user['id']]
        );
        return ['user' => $row];
    }

    /** @param array<string, string> $params */
    public static function update(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $allowed = ['name', 'locale'];
        $updates = [];
        $values = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $updates[] = "`$key` = ?";
                $values[] = $payload[$key];
            }
        }
        if ($updates === []) {
            return ['user' => DB::queryOne('SELECT id, email, name, role, locale FROM users WHERE id = ?', [$user['id']])];
        }
        $values[] = $user['id'];
        DB::execute('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?', $values);
        return ['user' => DB::queryOne('SELECT id, email, name, role, locale FROM users WHERE id = ?', [$user['id']])];
    }

    /** @param array<string, string> $params */
    public static function credits(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $row = DB::queryOne('SELECT credits FROM users WHERE id = ?', [$user['id']]);
        return ['credits' => (int) ($row['credits'] ?? 0)];
    }

    /** @param array<string, string> $params */
    public static function usage(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $rows = DB::query(
            'SELECT date, tokens_used FROM ai_usage WHERE user_id = ? ORDER BY date DESC LIMIT 30',
            [$user['id']]
        );
        return ['usage' => $rows];
    }
}
