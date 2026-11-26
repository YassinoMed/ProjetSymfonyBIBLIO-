<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PageControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->clear();
    }

    public function testHomePageShowsThePublicCollection(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Chaque livre mérite');
        self::assertCount(3, $crawler->filter('.collection-card'));
        self::assertGreaterThan(0, $crawler->filter('a[href="/catalogue"]')->count());
    }

    public function testCatalogIsPublicAndCanBeFilteredOnTheServer(): void
    {
        $crawler = $this->client->request('GET', '/catalogue?q=atlas');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Le catalogue');
        self::assertCount(1, $crawler->filter('[data-book-card]'));
        self::assertSelectorTextContains('[data-book-card]', 'Atlas des constellations disparues');
        self::assertSelectorTextContains('[data-catalog-count]', '1');
    }

    public function testCatalogDisplaysAnExplicitEmptyState(): void
    {
        $crawler = $this->client->request('GET', '/catalogue?q=aucun-resultat');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-book-card]'));
        self::assertCount(1, $crawler->filter('[data-catalog-empty]'));
    }

    public function testAuthenticatedMemberSeesLoanAndReservationFixtures(): void
    {
        $this->loginAs('membre@bibliotheque.test', 'ROLE_MEMBER');

        $crawler = $this->client->request('GET', '/membre');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bonjour Yasmine');
        self::assertGreaterThan(0, $crawler->filter('[data-loan-row]')->count());
        self::assertGreaterThan(0, $crawler->filter('[data-reservation-row]')->count());
        self::assertCount(1, $crawler->filter('[data-chat][data-chat-endpoint="/api/ai/generate"]'));
        self::assertNotSame('', (string) $crawler->filter('[data-chat-token]')->attr('data-chat-token'));
    }

    public function testAuthenticatedLibrarianSeesOperationalTasks(): void
    {
        $this->loginAs('bibliothecaire@bibliotheque.test', 'ROLE_LIBRARIAN');

        $crawler = $this->client->request('GET', '/bibliothecaire');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bonjour Amel');
        self::assertGreaterThan(0, $crawler->filter('[data-task-row]')->count());
    }

    public function testAuthenticatedAdministratorSeesAuditEvents(): void
    {
        $this->loginAs('admin@bibliotheque.test', 'ROLE_ADMIN');

        $crawler = $this->client->request('GET', '/administrateur');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bonjour Amine');
        self::assertGreaterThan(0, $crawler->filter('[data-audit-row]')->count());
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
