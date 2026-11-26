<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class LoginTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->clear();
    }

    public function testLoginPageIsPubliclyAccessible(): void
    {
        $crawler = $this->client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ravi de vous revoir');
        self::assertCount(1, $crawler->filter('input[name="_username"]'));
        self::assertCount(1, $crawler->filter('input[name="_password"]'));
        self::assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
    }

    public function testValidCredentialsAuthenticateTheUser(): void
    {
        $this->createUser('membre@bibliotheque.test', 'ROLE_MEMBER');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'membre@bibliotheque.test',
            '_password' => AppFixtures::DEMO_PASSWORD,
        ]);

        self::assertResponseRedirects('/');

        $token = self::getContainer()->get(TokenStorageInterface::class)->getToken();
        self::assertNotNull($token);
        self::assertSame('membre@bibliotheque.test', $token->getUserIdentifier());

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bienvenue à la bibliothèque');
    }

    public function testAuthenticatedUserIsRedirectedAwayFromLoginPage(): void
    {
        $this->client->loginUser($this->createUser('membre@bibliotheque.test', 'ROLE_MEMBER'));

        $this->client->request('GET', '/connexion');

        self::assertResponseRedirects('/');
    }

    public function testUserCanLogOut(): void
    {
        $this->client->loginUser($this->createUser('membre@bibliotheque.test', 'ROLE_MEMBER'));

        $this->client->request('GET', '/');
        $this->client->submitForm('Se déconnecter');

        self::assertResponseRedirects('/');

        $this->client->request('GET', '/membre');
        self::assertResponseRedirects('/connexion');
    }

    public function testInvalidCredentialsAreRejected(): void
    {
        $this->createUser('membre@bibliotheque.test', 'ROLE_MEMBER');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'membre@bibliotheque.test',
            '_password' => 'wrong-password',
        ]);

        self::assertResponseRedirects('/connexion');

        $this->client->followRedirect();
        self::assertSelectorTextContains('[role="alert"]', 'Identifiants invalides.');
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $this->createUser('membre@bibliotheque.test', 'ROLE_MEMBER');

        $this->client->request('POST', '/connexion', [
            '_username' => 'membre@bibliotheque.test',
            '_password' => AppFixtures::DEMO_PASSWORD,
        ]);

        self::assertResponseRedirects('/connexion');
    }

    private function createUser(string $email, string $role): User
    {
        $container = self::getContainer();

        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, AppFixtures::DEMO_PASSWORD),
        );

        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
