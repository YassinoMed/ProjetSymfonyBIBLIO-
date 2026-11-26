<?php

declare(strict_types=1);

namespace App\Tests\Functional\Chat;

use App\Agent\LlmClientInterface;
use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AllowMockObjectsWithoutExpectations]
final class ChatControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private LlmClientInterface&MockObject $llmClient;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->clear();

        $this->llmClient = $this->createMock(LlmClientInterface::class);
        self::getContainer()->set(LlmClientInterface::class, $this->llmClient);
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->jsonRequest('POST', '/chat', ['message' => 'Bonjour']);

        self::assertResponseRedirects('/connexion');
    }

    public function testUserWithoutMemberRoleIsForbidden(): void
    {
        $this->client->loginUser($this->createUser('visiteur@bibliotheque.test', []));

        $this->client->jsonRequest('POST', '/chat', ['message' => 'Bonjour']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMissingCsrfTokenIsRejected(): void
    {
        $this->llmClient->expects(self::never())->method('complete');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/chat', ['message' => 'Bonjour']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $this->llmClient->expects(self::never())->method('complete');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/chat', ['message' => 'Bonjour'], [
            'HTTP_X_CSRF_TOKEN' => 'jeton-invalide',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEmptyMessageIsRejectedWithUnprocessableEntity(): void
    {
        $this->llmClient->expects(self::never())->method('complete');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/chat', ['message' => '   '], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMemberReceivesHtmlEscapedAnswer(): void
    {
        $this->llmClient
            ->expects(self::once())
            ->method('complete')
            ->with(self::callback(static function (array $messages): bool {
                return count($messages) === 4
                    && 'system' === $messages[0]['role']
                    && 'user' === $messages[1]['role']
                    && 'Salut' === $messages[1]['content']
                    && 'assistant' === $messages[2]['role']
                    && 'Bonjour !' === $messages[2]['content']
                    && 'user' === $messages[3]['role']
                    && 'Quels sont les horaires ?' === $messages[3]['content'];
            }))
            ->willReturn('Réponse <script>alert("xss")</script> & infos');

        $this->client->loginUser($this->createUser($this->uniqueEmail(), ['ROLE_MEMBER']));

        $this->client->jsonRequest('POST', '/chat', [
            'message' => 'Quels sont les horaires ?',
            'history' => [
                ['role' => 'user', 'content' => 'Salut'],
                ['role' => 'assistant', 'content' => 'Bonjour !'],
            ],
        ], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","response":"Réponse &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; infos"}',
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
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = self::getContainer()->get(CsrfTokenManagerInterface::class);

        return $tokenManager->getToken('chat')->getValue();
    }

    private function uniqueEmail(): string
    {
        return \sprintf('membre-%s@bibliotheque.test', bin2hex(random_bytes(4)));
    }
}
