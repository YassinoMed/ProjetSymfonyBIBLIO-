<?php

declare(strict_types=1);

namespace App\Agent;

use App\Exception\LlmInvalidResponseException;
use App\Exception\LlmUnauthorizedException;
use App\Exception\LlmUnavailableException;

interface LlmClientInterface
{
    /**
     * @param list<array{role: string, content: string}> $messages
     *
     * @throws LlmInvalidResponseException
     * @throws LlmUnauthorizedException
     * @throws LlmUnavailableException
     */
    public function complete(array $messages): string;
}
