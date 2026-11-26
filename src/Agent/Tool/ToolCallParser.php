<?php

declare(strict_types=1);

namespace App\Agent\Tool;

/**
 * Isolates the strict-JSON tool-call protocol used while the upstream LLM
 * server does not support native function calling.
 */
final readonly class ToolCallParser
{
    public function parse(string $modelOutput): ?ParsedToolCall
    {
        $candidate = $this->extractJsonCandidate(trim($modelOutput));

        if (null === $candidate) {
            return null;
        }

        try {
            $decoded = json_decode($candidate, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!\is_array($decoded) || !isset($decoded['tool']) || !\is_string($decoded['tool'])) {
            return null;
        }

        $toolName = trim($decoded['tool']);
        if ('' === $toolName) {
            return null;
        }

        $arguments = $decoded['arguments'] ?? [];
        if (!\is_array($arguments)) {
            return null;
        }

        if ([] !== $arguments && array_is_list($arguments)) {
            return null;
        }

        /** @var array<string, mixed> $arguments */
        return new ParsedToolCall($toolName, $arguments);
    }

    private function extractJsonCandidate(string $text): ?string
    {
        if ('' === $text) {
            return null;
        }

        if (preg_match('/^```(?:json)?\s*\R(.*?)\R```$/s', $text, $matches) === 1) {
            $text = trim($matches[1]);
        }

        if (!str_starts_with($text, '{') || !str_ends_with($text, '}')) {
            return null;
        }

        return $text;
    }
}
