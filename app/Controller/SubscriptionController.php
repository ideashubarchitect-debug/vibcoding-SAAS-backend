<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class SubscriptionController
{
    /** @param array<string, string> $params */
    public static function index(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $sub = DB::queryOne(
            'SELECT s.id, s.plan_id, s.status, s.current_period_end, p.name as plan_name, p.credits_per_month 
             FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 1',
            [$user['id']]
        );
        return ['subscription' => $sub];
    }

    /** @param array<string, string> $params */
    public static function create(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $planId = (int) ($payload['plan_id'] ?? 0);
        if ($planId <= 0) {
            throw new \Exception('plan_id required', 400);
        }
        $plan = DB::queryOne('SELECT id, credits_per_month FROM plans WHERE id = ? AND active = 1', [$planId]);
        if (!$plan) {
            throw new \Exception('Plan not found', 404);
        }
        DB::execute(
            'INSERT INTO subscriptions (user_id, plan_id, status, current_period_start, current_period_end) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))',
            [$user['id'], $planId, 'active']
        );
        DB::execute('UPDATE users SET subscription_plan_id = ?, credits = credits + ? WHERE id = ?', [$planId, $plan['credits_per_month'], $user['id']]);
        return ['message' => 'Subscription created.', 'subscription' => DB::queryOne('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$user['id']])];
    }

    /** @param array<string, string> $params */
    public static function update(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $sub = DB::queryOne('SELECT id FROM subscriptions WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$sub) {
            throw new \Exception('Subscription not found', 404);
        }
        // Cancel or change plan
        return ['subscription' => $sub];
    }

    /** @param array<string, string> $params */
    public static function invoices(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $rows = DB::query('SELECT id, amount, currency, status, created_at FROM invoices WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$user['id']]);
        return ['invoices' => $rows];
    }
}
