<?php

declare(strict_types=1);

namespace Vibeable\Backend\Agent\Providers;

use Vibeable\Backend\Agent\AiProviderInterface;

final class DeepSeekProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $apiKey
    ) {
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{content: string, tokens_used: int}
     */
    public function chat(string $systemPrompt, array $messages): array
    {
        $payload = [
            'model' => 'deepseek-chat',
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'max_tokens' => 4096,
        ];

        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            throw new \RuntimeException('DeepSeek API error: ' . ($response ?: 'request failed'));
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';
        $tokens = (int) ($data['usage']['total_tokens'] ?? 0);
        return ['content' => $content, 'tokens_used' => $tokens];
    }
}
