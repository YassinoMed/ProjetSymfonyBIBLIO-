<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent;

use App\Agent\OpenAiCompatibleLlmClient;
use App\Exception\LlmInvalidResponseException;
use App\Exception\LlmUnauthorizedException;
use App\Exception\LlmUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenAiCompatibleLlmClientTest extends TestCase
{
    public function testItSendsAnOpenAiCompatibleChatCompletionRequest(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://llm.example.test/v1/chat/completions', $url);
            self::assertContains('Authorization: Bearer test-key', $options['headers']);
            self::assertSame(120.0, $options['timeout']);

            /** @var array{model: string, messages: list<array{role: string, content: string}>} $payload */
            $payload = json_decode((string) $options['body'], true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('test-model', $payload['model']);
            self::assertSame([
                ['role' => 'system', 'content' => 'System prompt'],
                ['role' => 'user', 'content' => 'User prompt'],
            ], $payload['messages']);

            return new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Model answer']],
                ],
            ]);
        });

        $client = $this->createClient($httpClient);

        self::assertSame('Model answer', $client->complete([
            ['role' => 'system', 'content' => 'System prompt'],
            ['role' => 'user', 'content' => 'User prompt'],
        ]));
    }

    public function testItThrowsAnUnauthorizedExceptionForA401Response(): void
    {
        $client = $this->createClient(new MockHttpClient(new JsonMockResponse(
            ['error' => ['message' => 'Invalid API key']],
            ['http_code' => 401],
        )));

        $this->expectException(LlmUnauthorizedException::class);

        $client->complete([['role' => 'user', 'content' => 'Hello']]);
    }

    public function testItRetriesOneTransportFailureAndThrowsUnavailableWhenItFailsAgain(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['error' => 'Connection timed out']),
            new MockResponse('', ['error' => 'Connection timed out']),
        ]);

        $client = $this->createClient($httpClient);

        try {
            $client->complete([['role' => 'user', 'content' => 'Hello']]);
            self::fail('An LlmUnavailableException should have been thrown.');
        } catch (LlmUnavailableException $e) {
            self::assertStringNotContainsString('test-key', $e->getMessage());
        }

        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testItThrowsAnInvalidResponseExceptionForInvalidJson(): void
    {
        $client = $this->createClient(new MockHttpClient(new MockResponse('<html>Invalid JSON</html>')));

        $this->expectException(LlmInvalidResponseException::class);

        $client->complete([['role' => 'user', 'content' => 'Hello']]);
    }

    public function testItThrowsAnInvalidResponseExceptionForAnEmptyAnswer(): void
    {
        $client = $this->createClient(new MockHttpClient(new JsonMockResponse([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => '']],
            ],
        ])));

        $this->expectException(LlmInvalidResponseException::class);

        $client->complete([['role' => 'user', 'content' => 'Hello']]);
    }

    public function testItThrowsAnUnavailableExceptionForAServerError(): void
    {
        $httpClient = new MockHttpClient([
            new JsonMockResponse(
                ['error' => ['message' => 'Temporary failure']],
                ['http_code' => 503],
            ),
            new JsonMockResponse(
                ['error' => ['message' => 'Temporary failure']],
                ['http_code' => 503],
            ),
        ]);
        $client = $this->createClient($httpClient);

        try {
            $client->complete([['role' => 'user', 'content' => 'Hello']]);
            self::fail('An LlmUnavailableException should have been thrown.');
        } catch (LlmUnavailableException $e) {
            self::assertStringNotContainsString('Temporary failure', $e->getMessage());
        }

        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testItRejectsAnInvalidBaseUrlWithoutCallingTheApi(): void
    {
        $httpClient = new MockHttpClient();
        $client = new OpenAiCompatibleLlmClient($httpClient, 'not-a-valid-url', 'test-key', 'test-model');

        $this->expectException(LlmUnavailableException::class);

        $client->complete([['role' => 'user', 'content' => 'Hello']]);
    }

    public function testItRetriesOnceAndReturnsAnswerWhenSecondAttemptSucceeds(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['error' => 'Connection timed out']),
            new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Recovered answer']],
                ],
            ]),
        ]);

        $client = $this->createClient($httpClient);

        self::assertSame('Recovered answer', $client->complete([['role' => 'user', 'content' => 'Hello']]));
        self::assertSame(2, $httpClient->getRequestsCount());
    }

    public function testItDoesNotRetryUnauthorizedResponses(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(
            ['error' => ['message' => 'Forbidden']],
            ['http_code' => 403],
        ));
        $client = $this->createClient($httpClient);

        try {
            $client->complete([['role' => 'user', 'content' => 'Hello']]);
            self::fail('An LlmUnauthorizedException should have been thrown.');
        } catch (LlmUnauthorizedException) {
            self::assertSame(1, $httpClient->getRequestsCount());
        }
    }

    public function testItRejectsAnEmptyModelWithoutCallingTheApi(): void
    {
        $httpClient = new MockHttpClient();
        $client = new OpenAiCompatibleLlmClient($httpClient, 'https://llm.example.test', 'test-key', '   ');

        $this->expectException(LlmUnavailableException::class);

        $client->complete([['role' => 'user', 'content' => 'Hello']]);
    }

    private function createClient(MockHttpClient $httpClient): OpenAiCompatibleLlmClient
    {
        return new OpenAiCompatibleLlmClient(
            $httpClient,
            'https://llm.example.test',
            'test-key',
            'test-model',
        );
    }
}
