<?php

declare(strict_types=1);

namespace App\Tests\Functional\Agent;

use App\Agent\Tool\AgentToolRegistry;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Copy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogToolsTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private AgentToolRegistry $registry;

    private int $foundationBookId;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->registry = self::getContainer()->get(AgentToolRegistry::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Penalty p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Loan l')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Reservation r')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Copy c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Author a')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category cat')->execute();
        $this->entityManager->clear();

        $sciFi = (new Category())->setName('Science Fiction');
        $romance = (new Category())->setName('Romance');
        $this->entityManager->persist($sciFi);
        $this->entityManager->persist($romance);

        $asimov = (new Author())->setName('Asimov')->setFirstname('Isaac');
        $austen = (new Author())->setName('Austen')->setFirstname('Jane');
        $this->entityManager->persist($asimov);
        $this->entityManager->persist($austen);

        $foundation = (new Book())
            ->setTitle('Foundation')
            ->setIsbn('9780451450524')
            ->setSummary('Galactic empire saga')
            ->setPublicationYear(1951)
            ->setCategory($sciFi)
            ->addAuthor($asimov);

        $availableCopy = (new Copy())
            ->setBarcode('BC-FOUND-1')
            ->setStatus('disponible')
            ->setCondition('bon')
            ->setBook($foundation);

        $borrowedCopy = (new Copy())
            ->setBarcode('BC-FOUND-2')
            ->setStatus('emprunté')
            ->setCondition('bon')
            ->setBook($foundation);

        $pride = (new Book())
            ->setTitle('Pride and Prejudice')
            ->setIsbn('9780141439518')
            ->setSummary('Classic novel')
            ->setPublicationYear(1813)
            ->setCategory($romance)
            ->addAuthor($austen);

        $unavailableCopy = (new Copy())
            ->setBarcode('BC-PRIDE-1')
            ->setStatus('emprunté')
            ->setCondition('bon')
            ->setBook($pride);

        $this->entityManager->persist($foundation);
        $this->entityManager->persist($availableCopy);
        $this->entityManager->persist($borrowedCopy);
        $this->entityManager->persist($pride);
        $this->entityManager->persist($unavailableCopy);
        $this->entityManager->flush();

        $this->foundationBookId = (int) $foundation->getId();
        $this->entityManager->clear();
    }

    public function testSearchBooksFiltersByQueryAuthorCategoryAndAvailability(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);

        $result = $this->registry->execute('search_books', [
            'query' => 'Foundation',
            'author' => 'Asimov',
            'category' => 'Science Fiction',
            'availableOnly' => true,
        ], $user);

        self::assertSame(1, $result['count']);
        self::assertCount(1, $result['books']);
        self::assertSame('Foundation', $result['books'][0]['title']);
        self::assertSame(1, $result['books'][0]['availableCopies']);
        self::assertSame(2, $result['books'][0]['totalCopies']);
    }

    public function testGetBookReturnsBookDetailsAndHandlesMissingId(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);

        $found = $this->registry->execute('get_book', ['bookId' => $this->foundationBookId], $user);

        self::assertTrue($found['found']);
        self::assertSame('Foundation', $found['book']['title']);
        self::assertCount(2, $found['book']['copies']);

        $missing = $this->registry->execute('get_book', ['bookId' => 999999], $user);

        self::assertFalse($missing['found']);
    }

    public function testGetPolicyIsRegisteredAndReadsContainerConfiguration(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);

        $result = $this->registry->execute('get_policy', [], $user);

        self::assertSame(21, $result['rules']['loan_duration_days']);
        self::assertSame(5, $result['rules']['max_active_loans']);
        self::assertSame(3, $result['rules']['max_reservations']);
    }
}
