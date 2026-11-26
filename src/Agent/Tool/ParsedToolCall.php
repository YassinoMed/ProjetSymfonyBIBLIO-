<?php

declare(strict_types=1);

namespace App\Agent\Tool;

final readonly class ParsedToolCall
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public string $tool,
        public array $arguments = [],
    ) {
    }
}
