<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\GeminiException;
use App\Service\GeminiClientService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GeminiClientServiceTest extends TestCase
{
    public function testGenerateTextReturnsTheModelAnswer(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Bonjour, comment puis-je aider ?']]]],
            ],
        ]));

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        self::assertSame('Bonjour, comment puis-je aider ?', $service->generateText('Bonjour'));
    }

    public function testTheApiKeyIsSentInAHeaderAndNeverInTheUrlOrBody(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertStringNotContainsString('test-key', $url);

            /** @var list<string> $headers */
            $headers = $options['headers'];
            self::assertContains('x-goog-api-key: test-key', $headers);

            /** @var string $body */
            $body = $options['body'];
            self::assertStringContainsString('Bonjour', $body);
            self::assertStringNotContainsString('test-key', $body);

            return new JsonMockResponse([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Réponse']]]],
                ],
            ]);
        });

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        self::assertSame('Réponse', $service->generateText('Bonjour'));
    }

    public function testSystemInstructionIsSentWhenProvided(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): ResponseInterface {
            /** @var string $body */
            $body = $options['body'];
            self::assertStringContainsString('systemInstruction', $body);
            self::assertStringContainsString('Tu es un assistant', $body);

            return new JsonMockResponse([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Réponse']]]],
                ],
            ]);
        });

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        self::assertSame('Réponse', $service->generateText('Bonjour', 'Tu es un assistant'));
    }

    public function testMissingApiKeyFailsWithoutCallingTheApi(): void
    {
        $httpClient = new MockHttpClient();

        $service = new GeminiClientService($httpClient, new NullLogger(), '');

        try {
            $service->generateText('Bonjour');
            self::fail('A GeminiException should have been thrown.');
        } catch (GeminiException $e) {
            self::assertSame('Le service IA n’est pas configuré.', $e->getMessage());
        }

        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function testQuotaExceededReturnsASafeMessage(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(
            ['error' => ['code' => 429, 'message' => 'Quota exceeded for internal project 123']],
            ['http_code' => 429],
        ));

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('Le quota de l’assistant est temporairement atteint. Réessayez dans quelques instants.');

        $service->generateText('Bonjour');
    }

    public function testUpstreamErrorDoesNotLeakTheResponseBody(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(
            ['error' => ['message' => 'API key not valid. Please pass a valid API key.']],
            ['http_code' => 400],
        ));

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        try {
            $service->generateText('Bonjour');
            self::fail('A GeminiException should have been thrown.');
        } catch (GeminiException $e) {
            self::assertSame('Le service IA est momentanément indisponible.', $e->getMessage());
            self::assertStringNotContainsString('API key', $e->getMessage());
        }
    }

    public function testEmptyAnswerIsRejected(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['candidates' => []]));

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('L’assistant n’a renvoyé aucune réponse.');

        $service->generateText('Bonjour');
    }

    public function testTransportErrorIsWrapped(): void
    {
        $httpClient = new MockHttpClient(static function (): ResponseInterface {
            throw new TransportException('Connection refused');
        });

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('Erreur de communication avec le service IA.');

        $service->generateText('Bonjour');
    }

    public function testNonJsonAnswerIsRejected(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('<html>Bad gateway</html>'));

        $service = new GeminiClientService($httpClient, new NullLogger(), 'test-key');

        $this->expectException(GeminiException::class);
        $this->expectExceptionMessage('Erreur de communication avec le service IA.');

        $service->generateText('Bonjour');
    }
}
