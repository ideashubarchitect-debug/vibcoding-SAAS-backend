<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Database\DB;

final class AdminController
{
    /** @param array<string, string> $params */
    public static function users(array $params, array $payload): array
    {
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($payload['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $rows = DB::query('SELECT id, email, name, role, credits, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
        $total = DB::queryOne('SELECT COUNT(*) as c FROM users')['c'] ?? 0;
        return ['users' => $rows, 'total' => (int) $total, 'page' => $page];
    }

    /** @param array<string, string> $params */
    public static function plans(array $params, array $payload): array
    {
        $rows = DB::query('SELECT * FROM plans ORDER BY sort_order, id');
        return ['plans' => $rows];
    }

    /** @param array<string, string> $params */
    public static function updatePlan(array $params, array $payload): array
    {
        $id = (int) ($params['id'] ?? 0);
        $plan = DB::queryOne('SELECT id FROM plans WHERE id = ?', [$id]);
        if (!$plan) {
            throw new \Exception('Plan not found', 404);
        }
        $allowed = ['name', 'slug', 'price_monthly', 'price_yearly', 'credits_per_month', 'features', 'active', 'sort_order'];
        $updates = [];
        $values = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $updates[] = "`$key` = ?";
                $values[] = is_array($payload[$key]) ? json_encode($payload[$key]) : $payload[$key];
            }
        }
        if ($updates !== []) {
            $values[] = $id;
            DB::execute('UPDATE plans SET ' . implode(', ', $updates) . ' WHERE id = ?', $values);
        }
        return ['plan' => DB::queryOne('SELECT * FROM plans WHERE id = ?', [$id])];
    }

    /** @param array<string, string> $params */
    public static function payments(array $params, array $payload): array
    {
        $rows = DB::query('SELECT p.*, u.email FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 100');
        return ['payments' => $rows];
    }

    /** @param array<string, string> $params */
    public static function usage(array $params, array $payload): array
    {
        $rows = DB::query('SELECT u.date, SUM(u.tokens_used) as tokens, COUNT(DISTINCT u.user_id) as users FROM ai_usage u GROUP BY u.date ORDER BY u.date DESC LIMIT 90');
        return ['usage' => $rows];
    }

    /** @param array<string, string> $params */
    public static function aiConfig(array $params, array $payload): array
    {
        $rows = DB::query('SELECT * FROM admin_settings WHERE `key` LIKE \'ai_%\'');
        $config = [];
        foreach ($rows as $r) {
            $config[$r['key']] = $r['value'];
        }
        return ['config' => $config];
    }

    /** @param array<string, string> $params */
    public static function updateAiConfig(array $params, array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (str_starts_with((string) $key, 'ai_')) {
                DB::execute('INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?', [$key, $value, $value]);
            }
        }
        return ['message' => 'Updated.'];
    }

    /** @param array<string, string> $params */
    public static function settings(array $params, array $payload): array
    {
        $rows = DB::query('SELECT `key`, `value` FROM admin_settings');
        $config = [];
        foreach ($rows as $r) {
            $config[$r['key']] = $r['value'];
        }
        return ['settings' => $config];
    }

    /** @param array<string, string> $params */
    public static function updateSettings(array $params, array $payload): array
    {
        foreach ($payload as $key => $value) {
            DB::execute('INSERT INTO admin_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?', [$key, $value, $value]);
        }
        return ['message' => 'Updated.'];
    }

    /** @param array<string, string> $params */
    public static function activity(array $params, array $payload): array
    {
        $rows = DB::query('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 200');
        return ['logs' => $rows];
    }

    /** @param array<string, string> $params */
    public static function credits(array $params, array $payload): array
    {
        $rows = DB::query('SELECT id, email, credits FROM users ORDER BY id LIMIT 500');
        return ['users' => $rows];
    }

    /** @param array<string, string> $params */
    public static function adjustCredits(array $params, array $payload): array
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $delta = (int) ($payload['delta'] ?? 0);
        if ($userId <= 0) {
            throw new \Exception('user_id required', 400);
        }
        DB::execute('UPDATE users SET credits = GREATEST(0, credits + ?) WHERE id = ?', [$delta, $userId]);
        $row = DB::queryOne('SELECT id, email, credits FROM users WHERE id = ?', [$userId]);
        return ['user' => $row];
    }
}
