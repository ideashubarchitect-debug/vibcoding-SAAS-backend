<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Service\AiAgentService;
use Vibeable\Backend\Database\DB;

final class AiController
{
    /** @param array<string, string> $params */
    public static function chat(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $projectId = (int) ($params['id'] ?? 0);
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            throw new \Exception('message required', 400);
        }
        $project = DB::queryOne('SELECT id, config FROM projects WHERE id = ? AND user_id = ?', [$projectId, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        $result = AiAgentService::chat((int) $project['id'], $user['id'], $message, $project['config'] ?? '{}');
        return $result;
    }

    /** @param array<string, string> $params */
    public static function generate(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $projectId = (int) ($params['id'] ?? 0);
        $prompt = trim((string) ($payload['prompt'] ?? $payload['message'] ?? ''));
        if ($prompt === '') {
            throw new \Exception('prompt or message required', 400);
        }
        $project = DB::queryOne('SELECT id, name, config FROM projects WHERE id = ? AND user_id = ?', [$projectId, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        $result = AiAgentService::generate((int) $project['id'], $user['id'], $prompt, $project['config'] ?? '{}');
        return $result;
    }

    /** @param array<string, string> $params */
    public static function edit(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $projectId = (int) ($params['id'] ?? 0);
        $instruction = trim((string) ($payload['instruction'] ?? $payload['message'] ?? ''));
        $elementId = $payload['element_id'] ?? null;
        if ($instruction === '') {
            throw new \Exception('instruction required', 400);
        }
        $project = DB::queryOne('SELECT id, config FROM projects WHERE id = ? AND user_id = ?', [$projectId, $user['id']]);
        if (!$project) {
            throw new \Exception('Project not found', 404);
        }
        $result = AiAgentService::edit((int) $project['id'], $user['id'], $instruction, $project['config'] ?? '{}', $elementId);
        return $result;
    }
}
