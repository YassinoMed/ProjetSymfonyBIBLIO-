<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessControlTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->clear();
    }

    public function testHomePageIsPublic(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Chaque livre mérite');
        self::assertGreaterThan(0, $crawler->filter('a[href="/connexion"]')->count());
    }

    #[DataProvider('provideProtectedPaths')]
    public function testAnonymousUsersAreRedirectedToLogin(string $path): void
    {
        $this->client->request('GET', $path);

        self::assertResponseRedirects('/connexion');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideProtectedPaths(): iterable
    {
        yield 'member space' => ['/membre'];
        yield 'librarian space' => ['/bibliothecaire'];
        yield 'admin space' => ['/administrateur'];
    }

    public function testMemberCanOnlyAccessMemberSpace(): void
    {
        $this->loginAs('membre@bibliotheque.test', 'ROLE_MEMBER');

        $this->client->request('GET', '/membre');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bonjour Yasmine');

        $this->client->request('GET', '/bibliothecaire');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/administrateur');
        self::assertResponseStatusCodeSame(403);
    }

    public function testLibrarianCanAccessMemberAndLibrarianSpaces(): void
    {
        $this->loginAs('bibliothecaire@bibliotheque.test', 'ROLE_LIBRARIAN');

        $this->client->request('GET', '/membre');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/bibliothecaire');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bonjour Amel');

        $this->client->request('GET', '/administrateur');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessEveryRoleSpace(): void
    {
        $this->loginAs('admin@bibliotheque.test', 'ROLE_ADMIN');

        foreach (['/membre', '/bibliothecaire', '/administrateur'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful();
        }

        $this->client->request('GET', '/administrateur');
        self::assertSelectorTextContains('h1', 'Bonjour Amine');
    }

    public function testAuthenticatedNavigationShowsRelevantSpaces(): void
    {
        $this->loginAs('bibliothecaire@bibliotheque.test', 'ROLE_LIBRARIAN');

        $crawler = $this->client->request('GET', '/');

        self::assertCount(1, $crawler->filter('.site-nav a[href="/membre"]'));
        self::assertCount(1, $crawler->filter('.site-nav a[href="/bibliothecaire"]'));
        self::assertCount(0, $crawler->filter('.site-nav a[href="/administrateur"]'));
    }

    private function loginAs(string $email, string $role): void
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

        $this->client->loginUser($user);
    }
}
