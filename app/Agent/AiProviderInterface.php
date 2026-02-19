<?php

declare(strict_types=1);

namespace Vibeable\Backend\Agent;

interface AiProviderInterface
{
    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{content: string, tokens_used?: int}
     */
    public function chat(string $systemPrompt, array $messages): array;
}
