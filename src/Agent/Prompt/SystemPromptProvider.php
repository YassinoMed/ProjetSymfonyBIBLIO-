<?php

declare(strict_types=1);

namespace App\Agent\Prompt;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SystemPromptProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/src/Agent/Prompt/system_prompt.txt')]
        private string $promptPath,
    ) {
    }

    public function getSystemPrompt(): string
    {
        if (!is_file($this->promptPath) || !is_readable($this->promptPath)) {
            throw new \RuntimeException('System prompt file is missing or unreadable.');
        }

        $content = file_get_contents($this->promptPath);

        if (false === $content || '' === trim($content)) {
            throw new \RuntimeException('System prompt file is empty.');
        }

        return trim($content);
    }
}
