<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Member;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * @return list<Reservation>
     */
    public function findActiveByMember(Member $member): array
    {
        /** @var list<Reservation> $reservations */
        $reservations = $this->createQueryBuilder('r')
            ->andWhere('r.member = :member')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE])
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();

        return $reservations;
    }

    public function countActiveByMember(Member $member): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.member = :member')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasActiveReservationForBook(Book $book): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.book = :book')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('book', $book)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function hasMemberActiveReservationForBook(Member $member, Book $book): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.member = :member')
            ->andWhere('r.book = :book')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('book', $book)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function getNextQueuePosition(Book $book): int
    {
        $max = $this->createQueryBuilder('r')
            ->select('COALESCE(MAX(r.position), 0)')
            ->andWhere('r.book = :book')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('book', $book)
            ->setParameter('statuses', [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE])
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function findNextPendingForBook(Book $book): ?Reservation
    {
        /** @var Reservation|null $reservation */
        $reservation = $this->createQueryBuilder('r')
            ->andWhere('r.book = :book')
            ->andWhere('r.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', Reservation::STATUS_PENDING)
            ->orderBy('r.position', 'ASC')
            ->addOrderBy('r.date', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $reservation;
    }

    /**
     * @return list<Reservation>
     */
    public function findExpiredAvailable(\DateTimeImmutable $now): array
    {
        /** @var list<Reservation> $reservations */
        $reservations = $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.expiresAt IS NOT NULL')
            ->andWhere('r.expiresAt < :now')
            ->setParameter('status', Reservation::STATUS_AVAILABLE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        return $reservations;
    }
}
