<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Book;
use App\Entity\Copy;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Penalty;
use App\Entity\Reservation;
use App\Repository\LoanRepository;
use App\Repository\PenaltyRepository;
use App\Repository\ReservationRepository;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LoanServiceTest extends TestCase
{
    private const array DEFAULT_POLICY = [
        'loan_duration_days' => 21,
        'max_active_loans' => 5,
        'max_renewals' => 1,
        'renewal_duration_days' => 14,
        'daily_penalty_eur' => 0.20,
        'max_penalty_per_book_eur' => 10.0,
        'blocking_penalty_threshold_eur' => 10.0,
        'blocking_overdue_days' => 30,
        'max_reservations' => 3,
        'reservation_pickup_days' => 5,
    ];

    public function testBorrowSucceedsAndSetsDueDateAndCopyStatus(): void
    {
        $now = new \DateTimeImmutable('2026-11-01 10:00:00');
        $member = $this->createActiveMember();
        $copy = $this->createCopy('disponible');

        $service = $this->createService();
        $loan = $service->borrow($member, $copy, $now);

        self::assertSame(Loan::STATUS_ACTIVE, $loan->getStatus());
        self::assertEquals($now, $loan->getStartedAt());
        self::assertEquals($now->modify('+21 days'), $loan->getDueAt());
        self::assertSame(0, $loan->getRenewalsCount());
        self::assertSame('emprunté', $copy->getStatus());
    }

    public function testBorrowFailsWhenMemberReachedMaxActiveLoans(): void
    {
        $member = $this->createActiveMember();
        $copy = $this->createCopy('disponible');

        $service = $this->createService(activeLoansCount: 5);

        $this->expectException(\DomainException::class);

        $service->borrow($member, $copy, new \DateTimeImmutable('2026-11-01'));
    }

    public function testBorrowFailsWhenMemberHasBlockingUnpaidPenalties(): void
    {
        $member = $this->createActiveMember();
        $copy = $this->createCopy('disponible');

        $service = $this->createService(unpaidPenaltyCents: 1000);

        $this->expectException(\DomainException::class);

        $service->borrow($member, $copy, new \DateTimeImmutable('2026-11-01'));
    }

    public function testBorrowFailsWhenMemberHasLoanOverdueBeyond30Days(): void
    {
        $now = new \DateTimeImmutable('2026-11-26');
        $member = $this->createActiveMember();
        $copy = $this->createCopy('disponible');

        $overdueLoan = new Loan($member, $this->createCopy('emprunté'), $now->modify('-55 days'), $now->modify('-31 days'));

        $service = $this->createService(activeLoans: [$overdueLoan]);

        $this->expectException(\DomainException::class);

        $service->borrow($member, $copy, $now);
    }

    public function testBorrowFailsWhenMembershipIsExpiredOrCopyIsNotBorrowable(): void
    {
        $now = new \DateTimeImmutable('2026-11-26');
        $expiredMember = (new Member('MEM-002', 'Expiré', $now->modify('-1 day')))->setStatus(Member::STATUS_ACTIVE);
        $service = $this->createService();

        try {
            $service->borrow($expiredMember, $this->createCopy('disponible'), $now);
            self::fail('Expected DomainException for expired membership.');
        } catch (\DomainException) {
        }

        $activeMember = $this->createActiveMember();

        $this->expectException(\DomainException::class);
        $service->borrow($activeMember, $this->createCopy('consultation_sur_place'), $now);
    }

    public function testRenewExtendsDueDateOnceAndFailsIfReservedOrAlreadyRenewed(): void
    {
        $now = new \DateTimeImmutable('2026-11-10');
        $member = $this->createActiveMember();
        $copy = $this->createCopy('emprunté');
        $loan = new Loan($member, $copy, $now->modify('-10 days'), $now->modify('+11 days'));

        $service = $this->createService();
        $service->renew($loan, $now);

        self::assertSame(1, $loan->getRenewalsCount());
        self::assertEquals($now->modify('+11 days +14 days'), $loan->getDueAt());

        try {
            $service->renew($loan, $now);
            self::fail('Second renewal should be refused.');
        } catch (\DomainException) {
        }

        $freshLoan = new Loan($member, $this->createCopy('emprunté'), $now->modify('-5 days'), $now->modify('+16 days'));
        $reservedService = $this->createService(hasPendingReservation: true);

        $this->expectException(\DomainException::class);
        $reservedService->renew($freshLoan, $now);
    }

    public function testReturnCalculatesAndCapsOverduePenalty(): void
    {
        $startedAt = new \DateTimeImmutable('2026-09-01');
        $dueAt = new \DateTimeImmutable('2026-09-22');
        $member = $this->createActiveMember();

        // 10 days late -> 10 * 20 cents = 200 cents (2.00 EUR)
        $copy1 = $this->createCopy('emprunté');
        $loan1 = new Loan($member, $copy1, $startedAt, $dueAt);
        $service = $this->createService();
        $service->returnCopy($loan1, $dueAt->modify('+10 days'));

        self::assertSame(Loan::STATUS_RETURNED, $loan1->getStatus());
        self::assertSame('disponible', $copy1->getStatus());
        self::assertCount(1, $loan1->getPenalties());
        $penalty1 = $loan1->getPenalties()->first();
        self::assertInstanceOf(Penalty::class, $penalty1);
        self::assertSame(200, $penalty1->getAmountCents());
        self::assertSame(Penalty::STATUS_DUE, $penalty1->getStatus());

        // 80 days late -> capped at 1000 cents (10.00 EUR)
        $copy2 = $this->createCopy('emprunté');
        $loan2 = new Loan($member, $copy2, $startedAt, $dueAt);
        $service->returnCopy($loan2, $dueAt->modify('+80 days'));

        $penalty2 = $loan2->getPenalties()->first();
        self::assertInstanceOf(Penalty::class, $penalty2);
        self::assertSame(1000, $penalty2->getAmountCents());
    }

    private function createActiveMember(): Member
    {
        return (new Member('MEM-001', 'Yasmine Nasri', new \DateTimeImmutable('2027-12-31')))
            ->setStatus(Member::STATUS_ACTIVE);
    }

    private function createCopy(string $status): Copy
    {
        $book = (new Book())
            ->setTitle('Foundation')
            ->setPublicationYear(1951);

        return (new Copy())
            ->setBarcode('BC-'.bin2hex(random_bytes(3)))
            ->setStatus($status)
            ->setCondition('bon')
            ->setBook($book);
    }

    /**
     * @param list<Loan> $activeLoans
     */
    private function createService(
        int $activeLoansCount = 0,
        int $unpaidPenaltyCents = 0,
        array $activeLoans = [],
        bool $hasPendingReservation = false,
    ): LoanService {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->method('countActiveByMember')->willReturn([] !== $activeLoans ? \count($activeLoans) : $activeLoansCount);
        $loanRepository->method('findActiveByMember')->willReturn($activeLoans);

        $penaltyRepository = $this->createMock(PenaltyRepository::class);
        $penaltyRepository->method('sumUnpaidCentsByMember')->willReturn($unpaidPenaltyCents);

        $reservationRepository = $this->createMock(ReservationRepository::class);
        $reservationRepository->method('hasActiveReservationForBook')->willReturn($hasPendingReservation);

        return new LoanService(
            $entityManager,
            $loanRepository,
            $penaltyRepository,
            $reservationRepository,
            self::DEFAULT_POLICY,
        );
    }
}
