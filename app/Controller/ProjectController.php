<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class ProjectController
{
    /** @param array<string, string> $params */
    public static function index(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $rows = DB::query('SELECT id, name, slug, subdomain, published_at, updated_at FROM projects WHERE user_id = ? ORDER BY updated_at DESC', [$user['id']]);
        return ['projects' => $rows];
    }

    /** @param array<string, string> $params */
    public static function store(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $name = trim((string) ($payload['name'] ?? 'Untitled Project'));
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name)) ?: 'project';
        $slug = $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        DB::execute(
            'INSERT INTO projects (user_id, name, slug, subdomain, config, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [$user['id'], $name, $slug, $slug, json_encode(['theme' => 'light', 'components' => []])]
        );
        $id = (int) DB::lastInsertId();
        $row = DB::queryOne('SELECT id, name, slug, subdomain, config, created_at, updated_at FROM projects WHERE id = ?', [$id]);
        return ['project' => $row];
    }

    /** @param array<string, string> $params */
    public static function show(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $row = DB::queryOne('SELECT id, name, slug, subdomain, config, published_at, updated_at FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$row) {
            throw new \Exception('Project not found', 404);
        }
        return ['project' => $row];
    }

    /** @param array<string, string> $params */
    public static function update(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $row = DB::queryOne('SELECT id FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$row) {
            throw new \Exception('Project not found', 404);
        }
        $allowed = ['name', 'config'];
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
            DB::execute('UPDATE projects SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?', $values);
        }
        return ['project' => DB::queryOne('SELECT id, name, slug, subdomain, config, updated_at FROM projects WHERE id = ?', [$id])];
    }

    /** @param array<string, string> $params */
    public static function destroy(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $n = DB::execute('DELETE FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if ($n === 0) {
            throw new \Exception('Project not found', 404);
        }
        return ['message' => 'Project deleted.'];
    }

    /** @param array<string, string> $params */
    public static function preview(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $id = (int) ($params['id'] ?? 0);
        $row = DB::queryOne('SELECT id, config, subdomain FROM projects WHERE id = ? AND user_id = ?', [$id, $user['id']]);
        if (!$row) {
            throw new \Exception('Project not found', 404);
        }
        $config = is_string($row['config'] ?? '') ? json_decode($row['config'], true) : ($row['config'] ?? []);
        return ['preview' => $config, 'subdomain' => $row['subdomain']];
    }
}
