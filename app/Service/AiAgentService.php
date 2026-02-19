<?php

declare(strict_types=1);

namespace Vibeable\Backend\Service;

use Vibeable\Backend\Database\DB;
use Vibeable\Backend\Agent\AiProviderInterface;
use Vibeable\Backend\Agent\AiProviderFactory;

/**
 * Agentic AI website builder: chat, generate, edit with memory and self-correction.
 */
final class AiAgentService
{
    private const CREDITS_PER_1K_TOKENS = 1;

    /**
     * @param array<string, mixed>|string $currentConfig
     * @return array{reply: string, config?: array, steps?: array, tokens_used?: int}
     */
    public static function chat(int $projectId, int $userId, string $message, array|string $currentConfig): array
    {
        $config = is_string($currentConfig) ? (json_decode($currentConfig, true) ?? []) : $currentConfig;
        $provider = self::getUserProvider($userId);
        $memory = self::getConversationMemory($projectId, 10);
        $systemPrompt = self::systemPromptForChat();
        $messages = [];
        foreach ($memory as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $provider->chat($systemPrompt, $messages);
        $tokensUsed = $response['tokens_used'] ?? 0;
        self::recordUsage($userId, $tokensUsed);
        self::deductCredits($userId, $tokensUsed);
        self::appendMemory($projectId, 'user', $message);
        self::appendMemory($projectId, 'assistant', $response['content']);

        $updatedConfig = $config;
        if (isset($response['config']) && is_array($response['config'])) {
            $updatedConfig = array_merge($config, $response['config']);
            self::saveProjectConfig($projectId, $updatedConfig);
        }

        return [
            'reply' => $response['content'],
            'config' => $updatedConfig,
            'tokens_used' => $tokensUsed,
        ];
    }

    /**
     * @param array<string, mixed>|string $currentConfig
     * @return array{config: array, html?: string, steps: array, tokens_used: int}
     */
    public static function generate(int $projectId, int $userId, string $prompt, array|string $currentConfig): array
    {
        $config = is_string($currentConfig) ? (json_decode($currentConfig, true) ?? []) : $currentConfig;
        $provider = self::getUserProvider($userId);
        $systemPrompt = self::systemPromptForGenerate();
        $userPrompt = "Create a website with the following description:\n\n" . $prompt;
        if (!empty($config['components'])) {
            $userPrompt .= "\n\nCurrent structure (JSON): " . json_encode($config);
        }

        $response = $provider->chat($systemPrompt, [['role' => 'user', 'content' => $userPrompt]]);
        $tokensUsed = $response['tokens_used'] ?? 0;
        self::recordUsage($userId, $tokensUsed);
        self::deductCredits($userId, $tokensUsed);

        $parsed = self::parseStructuredResponse($response['content']);
        $newConfig = array_merge($config, $parsed['config'] ?? []);
        self::saveProjectConfig($projectId, $newConfig);

        return [
            'config' => $newConfig,
            'html' => $parsed['html'] ?? null,
            'steps' => $parsed['steps'] ?? [['title' => 'Generated', 'status' => 'done']],
            'tokens_used' => $tokensUsed,
        ];
    }

    /**
     * @param array<string, mixed>|string $currentConfig
     * @return array{config: array, tokens_used: int}
     */
    public static function edit(int $projectId, int $userId, string $instruction, array|string $currentConfig, ?string $elementId = null): array
    {
        $config = is_string($currentConfig) ? (json_decode($currentConfig, true) ?? []) : $currentConfig;
        $provider = self::getUserProvider($userId);
        $systemPrompt = self::systemPromptForEdit();
        $userPrompt = "Instruction: " . $instruction . "\n\nCurrent config: " . json_encode($config);
        if ($elementId !== null) {
            $userPrompt .= "\nTarget element_id: " . $elementId;
        }

        $response = $provider->chat($systemPrompt, [['role' => 'user', 'content' => $userPrompt]]);
        $tokensUsed = $response['tokens_used'] ?? 0;
        self::recordUsage($userId, $tokensUsed);
        self::deductCredits($userId, $tokensUsed);

        $parsed = self::parseStructuredResponse($response['content']);
        $newConfig = array_merge($config, $parsed['config'] ?? []);
        self::saveProjectConfig($projectId, $newConfig);

        return [
            'config' => $newConfig,
            'tokens_used' => $tokensUsed,
        ];
    }

    private static function getUserProvider(int $userId): AiProviderInterface
    {
        $byok = DB::queryOne('SELECT provider, api_key FROM user_ai_keys WHERE user_id = ? AND active = 1', [$userId]);
        if ($byok) {
            return AiProviderFactory::create($byok['provider'], $byok['api_key']);
        }
        $defaultProvider = getenv('AI_DEFAULT_PROVIDER') ?: 'openai';
        $key = getenv('OPENAI_API_KEY') ?: getenv('ANTHROPIC_API_KEY') ?: '';
        return AiProviderFactory::create($defaultProvider, $key);
    }

    private static function systemPromptForChat(): string
    {
        return <<<'PROMPT'
You are the vibeable.dev AI website builder assistant. You help users create and edit websites via conversation.
Respond in a friendly, concise way. When the user asks for changes to their site, you may return a JSON block with key "config" containing updated website structure (components, theme, etc.).
Use the format: {"config": {...}} when suggesting structural changes. Otherwise reply in plain text.
PROMPT;
    }

    private static function systemPromptForGenerate(): string
    {
        return <<<'PROMPT'
You are the vibeable.dev website generator. Given a user description, output a JSON object with:
- "config": { "theme": "light"|"dark", "components": [ { "id": "...", "type": "hero"|"section"|"navbar"|"footer"|"text"|"image"|"cta", "props": {...} } ] }
- "html": optional HTML string for the main content area
- "steps": [ {"title": "...", "status": "done"|"pending"} ]

Use a clean, modern design system. Components should be responsive. Return only valid JSON inside a code block if needed.
PROMPT;
    }

    private static function systemPromptForEdit(): string
    {
        return <<<'PROMPT'
You are the vibeable.dev website editor. Given an instruction and current config (JSON), output a JSON object with:
- "config": updated full or partial config with the requested changes applied.
Return only valid JSON. Preserve existing data not mentioned in the instruction.
PROMPT;
    }

    /**
     * @return array{config?: array, html?: string, steps?: array}
     */
    private static function parseStructuredResponse(string $content): array
    {
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $content, $m)) {
            $decoded = json_decode($m[1], true);
            return is_array($decoded) ? $decoded : [];
        }
        if (preg_match('/(\{[\s\S]*\})/', $content, $m)) {
            $decoded = json_decode($m[1], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /** @return list<array{role: string, content: string}> */
    private static function getConversationMemory(int $projectId, int $limit): array
    {
        $rows = DB::query(
            'SELECT role, content FROM project_chat_memory WHERE project_id = ? ORDER BY id DESC LIMIT ?',
            [$projectId, $limit * 2]
        );
        return array_reverse(array_slice($rows, -$limit));
    }

    private static function appendMemory(int $projectId, string $role, string $content): void
    {
        DB::execute('INSERT INTO project_chat_memory (project_id, role, content) VALUES (?, ?, ?)', [$projectId, $role, $content]);
    }

    /** @param array<string, mixed> $config */
    private static function saveProjectConfig(int $projectId, array $config): void
    {
        DB::execute('UPDATE projects SET config = ?, updated_at = NOW() WHERE id = ?', [json_encode($config), $projectId]);
    }

    private static function recordUsage(int $userId, int $tokens): void
    {
        $date = date('Y-m-d');
        DB::execute(
            'INSERT INTO ai_usage (user_id, date, tokens_used) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE tokens_used = tokens_used + ?',
            [$userId, $date, $tokens, $tokens]
        );
    }

    private static function deductCredits(int $userId, int $tokens): void
    {
        $credits = (int) ceil($tokens / 1000) * self::CREDITS_PER_1K_TOKENS;
        if ($credits > 0) {
            DB::execute('UPDATE users SET credits = GREATEST(0, credits - ?) WHERE id = ?', [$credits, $userId]);
        }
    }
}
