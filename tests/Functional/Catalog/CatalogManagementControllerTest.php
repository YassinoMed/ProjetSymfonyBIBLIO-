<?php

declare(strict_types=1);

namespace App\Tests\Functional\Catalog;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Copy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CatalogManagementControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private Category $category;

    private Author $author;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Penalty p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Loan l')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Reservation r')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Copy c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Author a')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category cat')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Member m')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->clear();

        $this->category = (new Category())->setName('Imaginaire');
        $this->author = (new Author())->setName('Delacroix')->setFirstname('Claire');
        $this->entityManager->persist($this->category);
        $this->entityManager->persist($this->author);
        $this->entityManager->flush();
    }

    public function testPublicCatalogQueriesDatabaseBooks(): void
    {
        $book = (new Book())
            ->setTitle('Atlas des constellations disparues')
            ->setIsbn('9782070111222')
            ->setSummary('Un voyage stellaire')
            ->setPublicationYear(2022)
            ->setCategory($this->category)
            ->addAuthor($this->author);
        $copy = (new Copy())
            ->setBarcode('BC-ATLAS-1')
            ->setStatus('disponible')
            ->setCondition('bon')
            ->setBook($book);

        $this->entityManager->persist($book);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $crawler = $this->client->request('GET', '/catalogue?q=atlas');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-book-card]'));
        self::assertSelectorTextContains('[data-book-card]', 'Atlas des constellations disparues');
    }

    public function testMemberCannotAccessCatalogManagement(): void
    {
        $this->loginAs('member@bibliotheque.test', ['ROLE_MEMBER']);

        $this->client->request('GET', '/bibliothecaire/catalogue');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLibrarianCanCreateEditManageCopiesAndDeleteBook(): void
    {
        $this->loginAs('librarian@bibliotheque.test', ['ROLE_LIBRARIAN']);

        // Create book
        $crawler = $this->client->request('GET', '/bibliothecaire/catalogue/nouveau');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Enregistrer', [
            'book[title]' => 'Les Chroniques du Nord',
            'book[isbn]' => '9782070999888',
            'book[publicationYear]' => '2025',
            'book[summary]' => 'Récit polaire',
            'book[category]' => (string) $this->category->getId(),
            'book[authors]' => [(string) $this->author->getId()],
        ]);
        self::assertResponseRedirects();

        $created = $this->entityManager->getRepository(Book::class)->findOneBy(['title' => 'Les Chroniques du Nord']);
        self::assertInstanceOf(Book::class, $created);
        $bookId = (int) $created->getId();

        // Edit book
        $this->client->request('GET', \sprintf('/bibliothecaire/catalogue/%d/modifier', $bookId));
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Enregistrer', [
            'book[title]' => 'Les Chroniques du Grand Nord',
            'book[isbn]' => '9782070999888',
            'book[publicationYear]' => '2025',
            'book[summary]' => 'Édition révisée',
            'book[category]' => (string) $this->category->getId(),
            'book[authors]' => [(string) $this->author->getId()],
        ]);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        $updated = $this->entityManager->getRepository(Book::class)->find($bookId);
        self::assertInstanceOf(Book::class, $updated);
        self::assertSame('Les Chroniques du Grand Nord', $updated->getTitle());

        // Add copy
        $crawler = $this->client->request('GET', \sprintf('/bibliothecaire/catalogue/%d/modifier', $bookId));
        $this->client->submitForm('Ajouter un exemplaire', [
            'copy[barcode]' => 'BC-NORD-01',
            'copy[status]' => 'disponible',
            'copy[condition]' => 'bon',
        ]);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        $copy = $this->entityManager->getRepository(Copy::class)->findOneBy(['barcode' => 'BC-NORD-01']);
        self::assertInstanceOf(Copy::class, $copy);

        // Remove copy
        $crawler = $this->client->request('GET', \sprintf('/bibliothecaire/catalogue/%d/modifier', $bookId));
        $deleteCopyForm = $crawler->filter(\sprintf('form[action="/bibliothecaire/exemplaire/%d/supprimer"]', $copy->getId()))->form();
        $this->client->submit($deleteCopyForm);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Copy::class)->find($copy->getId()));

        // Delete book
        $crawler = $this->client->request('GET', '/bibliothecaire/catalogue');
        $deleteBookForm = $crawler->filter(\sprintf('form[action="/bibliothecaire/catalogue/%d/supprimer"]', $bookId))->form();
        $this->client->submit($deleteBookForm);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Book::class)->find($bookId));
    }

    /**
     * @param list<string> $roles
     */
    private function loginAs(string $email, array $roles): void
    {
        $container = self::getContainer();

        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'test-password'),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
    }
}
