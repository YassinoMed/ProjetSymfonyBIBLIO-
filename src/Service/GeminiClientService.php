<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\GeminiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(GeminiClientInterface::class)]
final readonly class GeminiClientService implements GeminiClientInterface
{
    private const string API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private string $geminiApiKey,
    ) {
    }

    public function generateText(string $userPrompt, string $systemInstruction = ''): string
    {
        if ('' === trim($this->geminiApiKey)) {
            throw new GeminiException('Le service IA n’est pas configuré.');
        }

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1024,
            ],
        ];

        if ('' !== $systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        try {
            $response = $this->httpClient->request('POST', self::API_ENDPOINT, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    // La clé passe par un en-tête : elle n'apparaît jamais dans l'URL ni dans les logs d'accès.
                    'x-goog-api-key' => $this->geminiApiKey,
                ],
                'json' => $payload,
                'timeout' => 15.0,
            ]);

            $statusCode = $response->getStatusCode();

            if (429 === $statusCode) {
                throw new GeminiException('Le quota de l’assistant est temporairement atteint. Réessayez dans quelques instants.');
            }

            if ($statusCode >= 400) {
                // On journalise le statut uniquement : ni la clé, ni le prompt, ni le corps de la réponse.
                $this->logger->error('Gemini API returned an error.', ['status' => $statusCode]);

                throw new GeminiException('Le service IA est momentanément indisponible.');
            }

            /** @var array{candidates?: list<array{content?: array{parts?: list<array{text?: string}>}}>} $data */
            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!\is_string($text) || '' === trim($text)) {
                throw new GeminiException('L’assistant n’a renvoyé aucune réponse.');
            }

            return $text;
        } catch (ExceptionInterface $e) {
            $this->logger->critical('Gemini API communication failure.', ['exception' => $e::class]);

            throw new GeminiException('Erreur de communication avec le service IA.', 0, $e);
        }
    }
}
