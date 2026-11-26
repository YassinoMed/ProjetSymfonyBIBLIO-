<?php

declare(strict_types=1);

namespace App\Tests\Functional\Chat;

use App\Agent\LlmClientInterface;
use App\Entity\User;
use App\Repository\ChatConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AllowMockObjectsWithoutExpectations]
final class ChatUiAndHistoryTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\ChatMessage m')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\ChatConversation c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->clear();
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/assistant');
        self::assertResponseRedirects('/connexion');

        $this->client->request('GET', '/chat/history');
        self::assertResponseRedirects('/connexion');
    }

    public function testMemberCanOpenChatPageAndPersistIsolatedHistory(): void
    {
        $alice = $this->createUser('alice-chat@bibliotheque.test', ['ROLE_MEMBER']);
        $bob = $this->createUser('bob-chat@bibliotheque.test', ['ROLE_MEMBER']);

        $llmStub = $this->createMock(LlmClientInterface::class);
        $llmStub->method('complete')->willReturn('Bonjour Alice, voici les règles.');
        self::getContainer()->set(LlmClientInterface::class, $llmStub);

        $this->client->loginUser($alice);
        $crawler = $this->client->request('GET', '/assistant');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-chat][data-chat-endpoint="/chat"]'));

        $this->client->jsonRequest('POST', '/chat', ['message' => 'Bonjour assistant'], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);
        self::assertResponseIsSuccessful();

        /** @var ChatConversationRepository $convRepo */
        $convRepo = self::getContainer()->get(ChatConversationRepository::class);
        $aliceConv = $convRepo->findLatestByUser($alice);
        self::assertNotNull($aliceConv);
        self::assertCount(2, $aliceConv->getMessages());

        $this->client->request('GET', '/chat/history');
        self::assertResponseIsSuccessful();
        $historyPayload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(2, $historyPayload['messages']);
        self::assertSame('Bonjour assistant', $historyPayload['messages'][0]['content']);

        // Bob must not see Alice's messages
        $this->client->loginUser($bob);
        $this->client->request('GET', '/chat/history');
        self::assertResponseIsSuccessful();
        $bobPayload = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $bobPayload['messages']);

        // Alice can reset her own history
        $this->client->loginUser($alice);
        $this->client->jsonRequest('POST', '/chat/reset', [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_X_CSRF_TOKEN' => $this->csrfToken(),
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->request('GET', '/chat/history');
        $afterReset = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $afterReset['messages']);
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles): User
    {
        $container = self::getContainer();

        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'test-password'),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function csrfToken(): string
    {
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = self::getContainer()->get(CsrfTokenManagerInterface::class);

        return $tokenManager->getToken('chat')->getValue();
    }
}
