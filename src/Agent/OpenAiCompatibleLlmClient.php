<?php

declare(strict_types=1);

namespace App\Agent;

use App\Exception\LlmInvalidResponseException;
use App\Exception\LlmUnauthorizedException;
use App\Exception\LlmUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(LlmClientInterface::class)]
final readonly class OpenAiCompatibleLlmClient implements LlmClientInterface
{
    private const float TIMEOUT_SECONDS = 120.0;
    private const int MAX_ATTEMPTS = 2;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(AGENT_BASE_URL)%')]
        private string $baseUrl,
        #[Autowire('%env(AGENT_API_KEY)%')]
        private string $apiKey,
        #[Autowire('%env(AGENT_MODEL)%')]
        private string $model,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function complete(array $messages): string
    {
        if ('' === trim($this->model)) {
            throw new LlmUnavailableException('The LLM model is not configured.');
        }

        $endpoint = $this->buildEndpoint();

        for ($attempt = 1; ; ++$attempt) {
            $startedAt = hrtime(true);
            $statusCode = null;

            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->model,
                        'messages' => $messages,
                    ],
                    'timeout' => self::TIMEOUT_SECONDS,
                ]);

                $statusCode = $response->getStatusCode();

                if (401 === $statusCode || 403 === $statusCode) {
                    throw new LlmUnauthorizedException('The LLM service rejected the configured credentials.');
                }

                if ($statusCode >= 500) {
                    if ($attempt < self::MAX_ATTEMPTS) {
                        $this->logFailure($startedAt, $statusCode, $attempt, LlmUnavailableException::class);
                        continue;
                    }

                    throw new LlmUnavailableException('The LLM service is temporarily unavailable.');
                }

                if ($statusCode >= 400) {
                    throw new LlmInvalidResponseException('The LLM service rejected the request.');
                }

                $data = $response->toArray();
                $content = $this->extractContent($data);
                $this->logSuccess($startedAt, $statusCode, $attempt, $data);

                return $content;
            } catch (LlmInvalidResponseException|LlmUnauthorizedException|LlmUnavailableException $e) {
                $this->logFailure($startedAt, $statusCode, $attempt, $e::class);

                throw $e;
            } catch (DecodingExceptionInterface $e) {
                $this->logFailure($startedAt, $statusCode, $attempt, LlmInvalidResponseException::class);

                throw new LlmInvalidResponseException('The LLM service returned an invalid JSON response.', 0, $e);
            } catch (TransportExceptionInterface $e) {
                $this->logFailure($startedAt, $statusCode, $attempt, LlmUnavailableException::class);

                if ($attempt < self::MAX_ATTEMPTS) {
                    continue;
                }

                throw new LlmUnavailableException('The LLM service is unavailable.', 0, $e);
            } catch (ExceptionInterface $e) {
                $this->logFailure($startedAt, $statusCode, $attempt, LlmUnavailableException::class);

                throw new LlmUnavailableException('The LLM service is unavailable.', 0, $e);
            }
        }
    }

    private function buildEndpoint(): string
    {
        if ('' === trim($this->baseUrl)) {
            throw new LlmUnavailableException('The LLM service is not configured.');
        }

        $baseUrl = rtrim(trim($this->baseUrl), '/');

        if (false === filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new LlmUnavailableException('The LLM service is not configured correctly.');
        }

        return $baseUrl.'/v1/chat/completions';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractContent(array $data): string
    {
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!\is_string($content) || '' === trim($content)) {
            throw new LlmInvalidResponseException('The LLM service returned an empty response.');
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function logSuccess(int $startedAt, int $httpStatus, int $attempt, array $data): void
    {
        if (null === $this->logger) {
            return;
        }

        $context = [
            'status' => 'success',
            'http_status' => $httpStatus,
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'model' => $this->model,
            'attempt' => $attempt,
        ];

        $usage = $data['usage'] ?? null;
        if (\is_array($usage)) {
            foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $key) {
                if (isset($usage[$key]) && \is_int($usage[$key])) {
                    $context[$key] = $usage[$key];
                }
            }
        }

        $this->logger->info('LLM completion succeeded.', $context);
    }

    private function logFailure(int $startedAt, ?int $httpStatus, int $attempt, string $errorType): void
    {
        $this->logger?->warning('LLM completion failed.', [
            'status' => 'error',
            'http_status' => $httpStatus,
            'duration_ms' => $this->elapsedMilliseconds($startedAt),
            'model' => $this->model,
            'attempt' => $attempt,
            'error_type' => $errorType,
        ]);
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
