<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Book;
use App\Entity\Copy;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ReservationServiceTest extends TestCase
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

    public function testReserveAssignsFifoPosition(): void
    {
        $member = $this->createActiveMember();
        $book = (new Book())->setTitle('Dune')->setPublicationYear(1965);
        $now = new \DateTimeImmutable('2026-11-26 10:00:00');

        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('countActiveByMember')->willReturn(1);
        $repository->method('hasMemberActiveReservationForBook')->willReturn(false);
        $repository->method('getNextQueuePosition')->willReturn(3);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            self::DEFAULT_POLICY,
        );

        $reservation = $service->reserve($member, $book, $now);

        self::assertSame(Reservation::STATUS_PENDING, $reservation->getStatus());
        self::assertSame(3, $reservation->getPosition());
        self::assertSame($now, $reservation->getDate());
    }

    public function testReserveFailsWhenMemberReachedMaxReservations(): void
    {
        $member = $this->createActiveMember();
        $book = (new Book())->setTitle('Dune')->setPublicationYear(1965);

        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('countActiveByMember')->willReturn(3);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            self::DEFAULT_POLICY,
        );

        $this->expectException(\DomainException::class);

        $service->reserve($member, $book);
    }

    public function testMarkNextAvailableSetsFiveDayPickupDeadlineAndReservesCopy(): void
    {
        $now = new \DateTimeImmutable('2026-11-26 12:00:00');
        $member = $this->createActiveMember();
        $book = (new Book())->setTitle('Dune')->setPublicationYear(1965);
        $copy = (new Copy())->setBarcode('BC-DUNE-1')->setStatus('disponible')->setCondition('bon')->setBook($book);

        $reservation = (new Reservation())
            ->setMember($member)
            ->setBook($book)
            ->setPosition(1)
            ->setStatus(Reservation::STATUS_PENDING)
            ->setDate($now->modify('-2 days'));

        $repository = $this->createMock(ReservationRepository::class);
        $repository->method('findNextPendingForBook')->willReturn($reservation);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            self::DEFAULT_POLICY,
        );

        $promoted = $service->markNextAvailable($book, $copy, $now);

        self::assertSame($reservation, $promoted);
        self::assertSame(Reservation::STATUS_AVAILABLE, $reservation->getStatus());
        self::assertEquals($now, $reservation->getAvailableAt());
        self::assertEquals($now->modify('+5 days'), $reservation->getExpiresAt());
        self::assertSame('réservé', $copy->getStatus());
    }

    public function testExpireOverdueReservationsMarksExpired(): void
    {
        $now = new \DateTimeImmutable('2026-11-26 12:00:00');
        $member = $this->createActiveMember();
        $book = (new Book())->setTitle('Dune')->setPublicationYear(1965);

        $expiredReservation = (new Reservation())
            ->setMember($member)
            ->setBook($book)
            ->setPosition(1)
            ->setStatus(Reservation::STATUS_AVAILABLE)
            ->setAvailableAt($now->modify('-6 days'))
            ->setExpiresAt($now->modify('-1 day'));

        $repository = $this->createMock(ReservationRepository::class);
        $repository->expects(self::once())->method('findExpiredAvailable')->with($now)->willReturn([$expiredReservation]);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            self::DEFAULT_POLICY,
        );

        $count = $service->expireOverdueReservations($now);

        self::assertSame(1, $count);
        self::assertSame(Reservation::STATUS_EXPIRED, $expiredReservation->getStatus());
    }

    public function testCancelMarksReservationCancelled(): void
    {
        $reservation = (new Reservation())
            ->setMember($this->createActiveMember())
            ->setBook((new Book())->setTitle('Dune')->setPublicationYear(1965))
            ->setPosition(1)
            ->setStatus(Reservation::STATUS_PENDING);

        $service = new ReservationService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ReservationRepository::class),
            self::DEFAULT_POLICY,
        );

        $service->cancel($reservation);

        self::assertSame(Reservation::STATUS_CANCELLED, $reservation->getStatus());
    }

    private function createActiveMember(): Member
    {
        return (new Member('MEM-001', 'Yasmine Nasri', new \DateTimeImmutable('2027-12-31')))
            ->setStatus(Member::STATUS_ACTIVE);
    }
}
