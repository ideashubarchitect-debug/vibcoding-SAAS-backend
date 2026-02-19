<?php

declare(strict_types=1);

namespace Vibeable\Backend\Middleware;

use Vibeable\Backend\Service\AuthService;

final class Auth
{
    public static function handle(array $params, callable $next): mixed
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) {
            throw new \Exception('Unauthorized', 401);
        }
        $token = $m[1];
        $user = AuthService::validateToken($token);
        if ($user === null) {
            throw new \Exception('Unauthorized', 401);
        }
        $GLOBALS['__vibeable_user'] = $user;
        return $next();
    }

    public static function optional(array $params, callable $next): mixed
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) {
            $user = AuthService::validateToken($m[1]);
            if ($user !== null) {
                $GLOBALS['__vibeable_user'] = $user;
            }
        }
        return $next();
    }
}
