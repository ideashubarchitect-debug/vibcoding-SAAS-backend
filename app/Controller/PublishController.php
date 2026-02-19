<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class PublishController
{
    /** @param array<string, string> $params */
    public static function publish(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $project = DB::queryOne('SELECT id, subdomain, config FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        $baseDomain = getenv('PUBLISH_BASE_DOMAIN') ?: 'vibeable.dev';
        DB::execute('UPDATE projects SET published_at = NOW(), updated_at = NOW() WHERE id = ?', [$id]);
        return [
            'message' => 'Published.',
            'url' => 'https://' . $project['subdomain'] . '.' . $baseDomain,
            'subdomain' => $project['subdomain'],
        ];
    }

    /** @param array<string, string> $params */
    public static function domains(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $project = DB::queryOne('SELECT id FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        $rows = DB::query('SELECT id, domain, ssl_status, verified_at FROM project_domains WHERE project_id = ?', [$id]);
        return ['domains' => $rows];
    }

    /** @param array<string, string> $params */
    public static function addDomain(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $domain = trim((string) ($payload['domain'] ?? ''));
        if ($domain === '') {
            throw new \Exception('domain required', 400);
        }
        $project = DB::queryOne('SELECT id FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        DB::execute('INSERT INTO project_domains (project_id, domain, ssl_status) VALUES (?, ?, ?)', [$id, $domain, 'pending']);
        return ['message' => 'Domain added. Follow DNS instructions to verify.', 'domain' => $domain];
    }
}
