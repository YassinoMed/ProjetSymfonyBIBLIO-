<?php

declare(strict_types=1);

namespace App\Service;

interface GeminiClientInterface
{
    /**
     * @throws \App\Exception\GeminiException when the upstream service fails or is not configured
     */
    public function generateText(string $userPrompt, string $systemInstruction = ''): string;
}
