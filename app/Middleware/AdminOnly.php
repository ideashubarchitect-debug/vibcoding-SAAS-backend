<?php

declare(strict_types=1);

namespace Vibeable\Backend\Middleware;

final class AdminOnly
{
    public static function handle(array $params, callable $next): mixed
    {
        $user = $GLOBALS['__vibeable_user'] ?? null;
        if ($user === null || (($user['role'] ?? 'user') !== 'admin')) {
            throw new \Exception('Forbidden', 403);
        }
        return $next();
    }
}
