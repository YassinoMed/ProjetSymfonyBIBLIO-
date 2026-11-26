<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * The LLM service rejected the configured credentials.
 */
final class LlmUnauthorizedException extends \RuntimeException
{
}
