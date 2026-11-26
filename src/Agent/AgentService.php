<?php

declare(strict_types=1);

namespace App\Agent;

use App\Agent\Prompt\SystemPromptProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AgentService
{
    public function __construct(
        private LlmClientInterface $llmClient,
        private SystemPromptProvider $systemPromptProvider,
        #[Autowire('%app.agent.max_history_messages%')]
        private int $maxHistoryMessages = 10,
    ) {
    }

    /**
     * @param list<array{role: string, content: string}> $history
     */
    public function reply(string $userMessage, array $history = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPromptProvider->getSystemPrompt()],
            ...$this->truncateHistory($history),
            ['role' => 'user', 'content' => trim($userMessage)],
        ];

        return $this->llmClient->complete($messages);
    }

    /**
     * @param list<array{role: string, content: string}> $history
     *
     * @return list<array{role: string, content: string}>
     */
    private function truncateHistory(array $history): array
    {
        if ($this->maxHistoryMessages <= 0) {
            return [];
        }

        $sanitized = [];

        foreach ($history as $entry) {
            if (!\in_array($entry['role'], ['user', 'assistant'], true)) {
                continue;
            }

            $content = trim($entry['content']);

            if ('' === $content) {
                continue;
            }

            $sanitized[] = [
                'role' => $entry['role'],
                'content' => $content,
            ];
        }

        return \array_slice($sanitized, -$this->maxHistoryMessages);
    }
}
