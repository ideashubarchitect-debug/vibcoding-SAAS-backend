<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class GdprController
{
    /** @param array<string, string> $params */
    public static function export(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $userId = (int) $user['id'];
        $userRow = DB::queryOne('SELECT id, email, name, role, locale, created_at FROM users WHERE id = ?', [$userId]);
        $projects = DB::query('SELECT id, name, slug, config, created_at, updated_at FROM projects WHERE user_id = ?', [$userId]);
        $subscriptions = DB::query('SELECT s.id, s.plan_id, s.status, s.current_period_end, p.name as plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.user_id = ?', [$userId]);
        return [
            'exported_at' => date('c'),
            'user' => $userRow,
            'projects' => $projects,
            'subscriptions' => $subscriptions,
        ];
    }

    /** @param array<string, string> $params */
    public static function deleteAccount(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $confirm = $payload['confirm'] ?? '';
        if ($confirm !== 'DELETE_MY_ACCOUNT') {
            throw new \Exception('Confirmation phrase required', 400);
        }
        $userId = (int) $user['id'];
        DB::beginTransaction();
        try {
            DB::execute('DELETE FROM ai_usage WHERE user_id = ?', [$userId]);
            DB::execute('DELETE FROM project_domains WHERE project_id IN (SELECT id FROM projects WHERE user_id = ?)', [$userId]);
            DB::execute('DELETE FROM projects WHERE user_id = ?', [$userId]);
            DB::execute('DELETE FROM subscriptions WHERE user_id = ?', [$userId]);
            DB::execute('DELETE FROM invoices WHERE user_id = ?', [$userId]);
            DB::execute('DELETE FROM users WHERE id = ?', [$userId]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        return ['message' => 'Account and all associated data have been deleted.'];
    }
}
