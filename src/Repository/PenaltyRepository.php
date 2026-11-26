<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Member;
use App\Entity\Penalty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Penalty>
 */
class PenaltyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Penalty::class);
    }

    public function sumUnpaidCentsByMember(Member $member): int
    {
        $sum = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amountCents), 0)')
            ->join('p.loan', 'l')
            ->andWhere('l.member = :member')
            ->andWhere('p.status = :status')
            ->setParameter('member', $member)
            ->setParameter('status', Penalty::STATUS_DUE)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $sum;
    }
}
