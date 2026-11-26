<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent;

use App\Agent\AgentService;
use App\Agent\OpenAiCompatibleLlmClient;
use App\Agent\Prompt\SystemPromptProvider;
use App\Exception\LlmInvalidResponseException;
use App\Exception\LlmUnauthorizedException;
use App\Exception\LlmUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class AgentMockHttpClientTest extends TestCase
{
    private string $tempPromptFile;

    protected function setUp(): void
    {
        $this->tempPromptFile = sys_get_temp_dir().'/agent_mock_http_'.bin2hex(random_bytes(4)).'.txt';
        file_put_contents($this->tempPromptFile, "Tu es l'assistant de la bibliothèque.\n");
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempPromptFile)) {
            unlink($this->tempPromptFile);
        }
    }

    public function testSuccessReturnsAnswerAndSendsExpectedPayload(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://llm.example.test/v1/chat/completions', $url);
            self::assertContains('Authorization: Bearer test-key', $options['headers']);

            /** @var array{model: string, messages: list<array{role: string, content: string}>} $payload */
            $payload = json_decode((string) $options['body'], true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('test-model', $payload['model']);
            self::assertSame([
                ['role' => 'system', 'content' => "Tu es l'assistant de la bibliothèque."],
                ['role' => 'user', 'content' => 'Question 2'],
                ['role' => 'assistant', 'content' => 'Réponse 2'],
                ['role' => 'user', 'content' => 'Question finale'],
            ], $payload['messages']);

            return new JsonMockResponse([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Réponse finale du modèle']],
                ],
            ]);
        });

        $agent = $this->createAgent($httpClient, maxHistoryMessages: 2);

        $answer = $agent->reply('Question finale', [
            ['role' => 'user', 'content' => 'Question 1'],
            ['role' => 'assistant', 'content' => 'Réponse 1'],
            ['role' => 'user', 'content' => 'Question 2'],
            ['role' => 'assistant', 'content' => 'Réponse 2'],
        ]);

        self::assertSame('Réponse finale du modèle', $answer);
    }

    public function testUnauthorized401ThrowsLlmUnauthorizedException(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(
            ['error' => ['message' => 'Invalid key']],
            ['http_code' => 401],
        ));

        $this->expectException(LlmUnauthorizedException::class);

        $this->createAgent($httpClient)->reply('Bonjour');
    }

    public function testTimeoutRetriesOnceAndThrowsLlmUnavailableException(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['error' => 'Operation timed out']),
            new MockResponse('', ['error' => 'Operation timed out']),
        ]);

        $agent = $this->createAgent($httpClient);

        try {
            $agent->reply('Bonjour');
            self::fail('LlmUnavailableException was expected.');
        } catch (LlmUnavailableException) {
            self::assertSame(2, $httpClient->getRequestsCount());
        }
    }

    public function testInvalidJsonThrowsLlmInvalidResponseException(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('not-valid-json'));

        $this->expectException(LlmInvalidResponseException::class);

        $this->createAgent($httpClient)->reply('Bonjour');
    }

    public function testEmptyAnswerThrowsLlmInvalidResponseException(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => '   ']],
            ],
        ]));

        $this->expectException(LlmInvalidResponseException::class);

        $this->createAgent($httpClient)->reply('Bonjour');
    }

    private function createAgent(MockHttpClient $httpClient, int $maxHistoryMessages = 10): AgentService
    {
        return new AgentService(
            new OpenAiCompatibleLlmClient(
                $httpClient,
                'https://llm.example.test',
                'test-key',
                'test-model',
            ),
            new SystemPromptProvider($this->tempPromptFile),
            $maxHistoryMessages,
        );
    }
}
