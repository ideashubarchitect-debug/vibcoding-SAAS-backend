<?php

declare(strict_types=1);

namespace Vibeable\Backend\Agent;

use Vibeable\Backend\Agent\Providers\OpenAiProvider;

final class AiProviderFactory
{
    public static function create(string $provider, string $apiKey): AiProviderInterface
    {
        return match (strtolower($provider)) {
            'openai' => new OpenAiProvider($apiKey),
            'anthropic' => new Providers\AnthropicProvider($apiKey),
            'grok' => new Providers\GrokProvider($apiKey),
            'deepseek' => new Providers\DeepSeekProvider($apiKey),
            'zhipu' => new Providers\ZhipuProvider($apiKey),
            default => new OpenAiProvider($apiKey),
        };
    }
}
