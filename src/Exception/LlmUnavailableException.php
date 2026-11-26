<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * The LLM service is unavailable or cannot be reached.
 */
final class LlmUnavailableException extends \RuntimeException
{
}
