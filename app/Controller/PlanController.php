<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Database\DB;

final class PlanController
{
    /** @param array<string, string> $params */
    public static function index(array $params, array $payload): array
    {
        $rows = DB::query('SELECT id, name, slug, price_monthly, price_yearly, credits_per_month, features FROM plans WHERE active = 1 ORDER BY sort_order, id');
        return ['plans' => $rows];
    }

    /** @param array<string, string> $params */
    public static function show(array $params, array $payload): array
    {
        $id = $params['id'] ?? '';
        $row = DB::queryOne('SELECT id, name, slug, price_monthly, price_yearly, credits_per_month, features FROM plans WHERE (id = ? OR slug = ?) AND active = 1', [$id, $id]);
        if (!$row) {
            throw new \Exception('Plan not found', 404);
        }
        return ['plan' => $row];
    }
}
