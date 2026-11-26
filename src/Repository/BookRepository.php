<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return list<Book>
     */
    public function searchCatalog(
        ?string $query = null,
        ?string $author = null,
        ?string $category = null,
        bool $availableOnly = false,
        int $limit = 10,
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->distinct()
            ->leftJoin('b.authors', 'a')
            ->leftJoin('b.category', 'c')
            ->leftJoin('b.copies', 'cp')
            ->orderBy('b.title', 'ASC')
            ->setMaxResults(max(1, min(20, $limit)));

        $normalizedQuery = trim((string) $query);
        if ('' !== $normalizedQuery) {
            $qb->andWhere(
                'LOWER(b.title) LIKE LOWER(:query) OR LOWER(COALESCE(b.summary, \'\')) LIKE LOWER(:query) OR LOWER(COALESCE(b.isbn, \'\')) LIKE LOWER(:query) OR LOWER(a.name) LIKE LOWER(:query) OR LOWER(COALESCE(a.firstname, \'\')) LIKE LOWER(:query)',
            )
                ->setParameter('query', '%'.$normalizedQuery.'%');
        }

        $normalizedAuthor = trim((string) $author);
        if ('' !== $normalizedAuthor) {
            $qb->andWhere('LOWER(a.name) LIKE LOWER(:author) OR LOWER(COALESCE(a.firstname, \'\')) LIKE LOWER(:author)')
                ->setParameter('author', '%'.$normalizedAuthor.'%');
        }

        $normalizedCategory = trim((string) $category);
        if ('' !== $normalizedCategory) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:category)')
                ->setParameter('category', '%'.$normalizedCategory.'%');
        }

        if ($availableOnly) {
            $qb->andWhere('cp.status IN (:availableStatuses)')
                ->setParameter('availableStatuses', ['disponible', 'available']);
        }

        /** @var list<Book> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
