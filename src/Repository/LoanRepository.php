<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * @return list<Loan>
     */
    public function findActiveByMember(Member $member): array
    {
        /** @var list<Loan> $loans */
        $loans = $this->createQueryBuilder('l')
            ->andWhere('l.member = :member')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->orderBy('l.dueAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $loans;
    }

    public function countActiveByMember(Member $member): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.member = :member')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
