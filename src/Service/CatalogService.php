<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogFilter;
use App\Entity\Book;
use App\Entity\Copy;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogService
{
    private const array AVAILABLE_STATUSES = ['disponible', 'available'];
    private const array ACCENTS = ['ochre', 'cobalt', 'moss', 'plum', 'vermilion'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookRepository $bookRepository,
        private CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     author: string,
     *     category: string,
     *     status: string,
     *     statusLabel: string,
     *     year: int,
     *     copies: int,
     *     accent: string,
     *     summary: string,
     *     isbn: string,
     * }>
     */
    public function searchForView(CatalogFilter $filter): array
    {
        $books = $this->bookRepository->searchCatalog(
            query: $filter->query(),
            category: '' !== $filter->selectedCategory() ? $filter->selectedCategory() : null,
            availableOnly: 'available' === $filter->selectedStatus(),
            limit: 50,
        );

        $items = [];
        foreach ($books as $index => $book) {
            $item = $this->toViewItem($book, $index);

            if ('' !== $filter->selectedStatus() && $item['status'] !== $filter->selectedStatus()) {
                continue;
            }

            $items[] = $item;
        }

        usort($items, static function (array $a, array $b) use ($filter): int {
            return match ($filter->selectedSort()) {
                'year' => $b['year'] <=> $a['year'],
                'availability' => ($b['status'] === 'available') <=> ($a['status'] === 'available'),
                default => strcasecmp($a['title'], $b['title']),
            };
        });

        return $items;
    }

    /**
     * @return list<string>
     */
    public function listCategories(): array
    {
        $names = [];
        foreach ($this->categoryRepository->findBy([], ['name' => 'ASC']) as $category) {
            $name = $category->getName();
            if ('' !== $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return list<Book>
     */
    public function listAllBooks(): array
    {
        /** @var list<Book> $books */
        $books = $this->bookRepository->findBy([], ['title' => 'ASC']);

        return $books;
    }

    public function saveBook(Book $book): Book
    {
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $book;
    }

    public function deleteBook(Book $book): void
    {
        foreach ($book->getCopies() as $copy) {
            if (\in_array($copy->getStatus(), ['emprunté', 'borrowed'], true)) {
                throw new \DomainException('Impossible de retirer un livre ayant un exemplaire emprunté.');
            }
        }

        foreach ($book->getCopies()->toArray() as $copy) {
            $this->entityManager->remove($copy);
        }

        foreach ($book->getReservations()->toArray() as $reservation) {
            $this->entityManager->remove($reservation);
        }

        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }

    public function addCopy(Book $book, Copy $copy): Copy
    {
        $copy->setBook($book);
        $book->addCopy($copy);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }

    public function removeCopy(Copy $copy): void
    {
        if (\in_array($copy->getStatus(), ['emprunté', 'borrowed'], true)) {
            throw new \DomainException('Impossible de supprimer un exemplaire actuellement emprunté.');
        }

        $this->entityManager->remove($copy);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     author: string,
     *     category: string,
     *     status: string,
     *     statusLabel: string,
     *     year: int,
     *     copies: int,
     *     accent: string,
     *     summary: string,
     *     isbn: string,
     * }
     */
    private function toViewItem(Book $book, int $index): array
    {
        $authors = [];
        foreach ($book->getAuthors() as $author) {
            $fullName = trim(\sprintf('%s %s', (string) $author->getFirstname(), (string) $author->getName()));
            if ('' !== $fullName) {
                $authors[] = $fullName;
            }
        }

        $copies = $book->getCopies();
        $copyCount = $copies->count();
        [$status, $statusLabel] = $this->resolveAvailability($copies->toArray());

        return [
            'id' => (int) $book->getId(),
            'title' => (string) $book->getTitle(),
            'author' => [] !== $authors ? implode(', ', $authors) : 'Auteur inconnu',
            'category' => $book->getCategory()?->getName() ?? 'Général',
            'status' => $status,
            'statusLabel' => $statusLabel,
            'year' => (int) $book->getPublicationYear(),
            'copies' => $copyCount,
            'accent' => self::ACCENTS[$index % \count(self::ACCENTS)],
            'summary' => $book->getSummary() ?? '',
            'isbn' => $book->getIsbn() ?? '',
        ];
    }

    /**
     * @param list<Copy> $copies
     *
     * @return array{string, string}
     */
    private function resolveAvailability(array $copies): array
    {
        foreach ($copies as $copy) {
            if (\in_array($copy->getStatus(), self::AVAILABLE_STATUSES, true)) {
                return ['available', 'Disponible'];
            }
        }

        foreach ($copies as $copy) {
            if ('réservé' === $copy->getStatus()) {
                return ['reserved', 'Réservé'];
            }
            if (\in_array($copy->getStatus(), ['en_réparation', 'en réparation'], true)) {
                return ['repair', 'En réparation'];
            }
        }

        return ['loan', 'Emprunté'];
    }
}
