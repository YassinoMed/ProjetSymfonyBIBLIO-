<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\Copy;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ReservationService
{
    /**
     * @param array<string, int|float|string|bool> $policy
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        #[Autowire('%app.policy%')]
        private array $policy,
    ) {
    }

    public function reserve(Member $member, Book $book, ?\DateTimeImmutable $now = null): Reservation
    {
        $now ??= new \DateTimeImmutable();

        if (!$member->isActiveAt($now)) {
            throw new \DomainException('Le membre doit avoir une cotisation active pour réserver.');
        }

        $maxReservations = (int) ($this->policy['max_reservations'] ?? 3);
        if ($this->reservationRepository->countActiveByMember($member) >= $maxReservations) {
            throw new \DomainException('Le plafond de réservations simultanées est atteint.');
        }

        if ($this->reservationRepository->hasMemberActiveReservationForBook($member, $book)) {
            throw new \DomainException('Vous avez déjà une réservation active pour ce livre.');
        }

        $reservation = (new Reservation())
            ->setMember($member)
            ->setBook($book)
            ->setDate($now)
            ->setStatus(Reservation::STATUS_PENDING)
            ->setPosition($this->reservationRepository->getNextQueuePosition($book));

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $reservation;
    }

    public function markNextAvailable(Book $book, Copy $copy, ?\DateTimeImmutable $now = null): ?Reservation
    {
        $now ??= new \DateTimeImmutable();
        $next = $this->reservationRepository->findNextPendingForBook($book);

        if (null === $next) {
            return null;
        }

        $pickupDays = (int) ($this->policy['reservation_pickup_days'] ?? 5);
        $next->setStatus(Reservation::STATUS_AVAILABLE)
            ->setAvailableAt($now)
            ->setExpiresAt($now->modify(\sprintf('+%d days', $pickupDays)));
        $copy->setStatus('réservé');

        $this->entityManager->flush();

        return $next;
    }

    public function expireOverdueReservations(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $expired = $this->reservationRepository->findExpiredAvailable($now);

        foreach ($expired as $reservation) {
            $reservation->setStatus(Reservation::STATUS_EXPIRED);
        }

        if ([] !== $expired) {
            $this->entityManager->flush();
        }

        return \count($expired);
    }

    public function cancel(Reservation $reservation): Reservation
    {
        if (!\in_array($reservation->getStatus(), [Reservation::STATUS_PENDING, Reservation::STATUS_AVAILABLE], true)) {
            throw new \DomainException('Cette réservation ne peut plus être annulée.');
        }

        $reservation->setStatus(Reservation::STATUS_CANCELLED);
        $this->entityManager->flush();

        return $reservation;
    }
}
