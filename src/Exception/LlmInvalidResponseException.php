<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * The LLM service returned a response that cannot be used.
 */
final class LlmInvalidResponseException extends \RuntimeException
{
}
