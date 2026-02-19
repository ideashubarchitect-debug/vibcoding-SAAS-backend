<?php

declare(strict_types=1);

namespace Vibeable\Backend\Agent\Providers;

use Vibeable\Backend\Agent\AiProviderInterface;

final class AnthropicProvider implements AiProviderInterface
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
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 4096,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            throw new \RuntimeException('Anthropic API error: ' . ($response ?: 'request failed'));
        }

        $data = json_decode($response, true);
        $content = $data['content'][0]['text'] ?? '';
        $tokens = (int) (($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0));
        return ['content' => $content, 'tokens_used' => $tokens];
    }
}
