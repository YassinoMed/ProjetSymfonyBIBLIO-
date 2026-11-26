<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ai;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Exception\GeminiException;
use App\Service\GeminiClientInterface;
use App\Service\GeminiClientService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
final class AiControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private GeminiClientInterface&MockObject $geminiClient;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->clear();

        $this->geminiClient = $this->createMock(GeminiClientInterface::class);
        self::getContainer()->set(GeminiClientService::class, $this->geminiClient);
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour']);

        self::assertResponseRedirects('/connexion');
    }

    public function testUserWithoutMemberRoleIsForbidden(): void
    {
        $this->client->loginUser($this->createUser('visiteur@bibliotheque.test', []));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMissingCsrfTokenIsRejected(): void
    {
        $this->geminiClient->expects(self::never())->method('generateText');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $this->geminiClient->expects(self::never())->method('generateText');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], [
            'HTTP_X_CSRF_TOKEN' => 'jeton-invalide',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberReceivesTheGeneratedAnswer(): void
    {
        $this->geminiClient
            ->expects(self::once())
            ->method('generateText')
            ->with('Bonjour', self::isString())
            ->willReturn('Voici une réponse.');

        $this->client->loginUser($this->createUser('membre@bibliotheque.test', ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], [
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","response":"Voici une réponse."}',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testInvalidPayloadIsRejected(array $payload): void
    {
        $this->geminiClient->expects(self::never())->method('generateText');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'empty prompt' => [['prompt' => '']];
        yield 'too short' => [['prompt' => 'ab']];
        yield 'too long' => [['prompt' => str_repeat('a', 2001)]];
        yield 'missing prompt' => [[]];
    }

    public function testUnsupportedContentTypeIsRejected(): void
    {
        $this->geminiClient->expects(self::never())->method('generateText');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->request('POST', '/api/ai/generate', [], [], ['CONTENT_TYPE' => 'text/plain'], 'Bonjour');

        self::assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    public function testMalformedJsonIsRejected(): void
    {
        $this->geminiClient->expects(self::never())->method('generateText');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->request('POST', '/api/ai/generate', [], [], ['CONTENT_TYPE' => 'application/json'], '{"prompt":');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRateLimitIsEnforced(): void
    {
        $this->geminiClient->method('generateText')->willReturn('Réponse');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $server = ['HTTP_X_CSRF_TOKEN' => $this->csrfToken()];

        for ($i = 0; $i < 10; ++$i) {
            $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], $server);
            self::assertResponseIsSuccessful();
        }

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], $server);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertTrue($this->client->getResponse()->headers->has('Retry-After'));
    }

    public function testGeminiFailureReturnsBadGateway(): void
    {
        $this->geminiClient
            ->method('generateText')
            ->willThrowException(new GeminiException('Le service IA est momentanément indisponible.'));

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], [
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_GATEWAY);
        self::assertJsonStringEqualsJsonString(
            '{"error":"Le service IA est momentanément indisponible."}',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testUnexpectedFailureDoesNotLeakInternalDetails(): void
    {
        $this->geminiClient
            ->method('generateText')
            ->willThrowException(new \RuntimeException('internal stack trace with secret details'));

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/api/ai/generate', ['prompt' => 'Bonjour'], [
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_GATEWAY);
        self::assertStringNotContainsString(
            'internal stack trace with secret details',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles): User
    {
        $container = self::getContainer();

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, AppFixtures::DEMO_PASSWORD),
        );

        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function csrfToken(): string
    {
        $crawler = $this->client->request('GET', '/membre');
        self::assertResponseIsSuccessful();

        $token = (string) $crawler->filter('[data-chat-token]')->attr('data-chat-token');
        self::assertNotSame('', $token);

        return $token;
    }

    private function uniqueEmail(): string
    {
        return \sprintf('membre-%s@bibliotheque.test', bin2hex(random_bytes(4)));
    }
}
