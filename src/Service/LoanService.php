<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Copy;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Penalty;
use App\Repository\LoanRepository;
use App\Repository\PenaltyRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LoanService
{
    private const array BORROWABLE_COPY_STATUSES = ['disponible', 'available'];

    /**
     * @param array<string, int|float|string|bool> $policy
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private PenaltyRepository $penaltyRepository,
        private ReservationRepository $reservationRepository,
        #[Autowire('%app.policy%')]
        private array $policy,
    ) {
    }

    public function borrow(Member $member, Copy $copy, ?\DateTimeImmutable $now = null): Loan
    {
        $now ??= new \DateTimeImmutable();

        if (!$member->isActiveAt($now)) {
            throw new \DomainException('Le membre doit avoir une cotisation active pour emprunter.');
        }

        if (!\in_array($copy->getStatus(), self::BORROWABLE_COPY_STATUSES, true)) {
            throw new \DomainException('Cet exemplaire n’est pas empruntable.');
        }

        $maxLoans = (int) ($this->policy['max_active_loans'] ?? 5);
        if ($this->loanRepository->countActiveByMember($member) >= $maxLoans) {
            throw new \DomainException('Le plafond d’emprunts simultanés est atteint.');
        }

        $blockingPenaltyCents = (int) round(((float) ($this->policy['blocking_penalty_threshold_eur'] ?? 10.0)) * 100);
        if ($this->penaltyRepository->sumUnpaidCentsByMember($member) >= $blockingPenaltyCents) {
            throw new \DomainException('Prêt bloqué en raison de pénalités impayées.');
        }

        $blockingOverdueDays = (int) ($this->policy['blocking_overdue_days'] ?? 30);
        foreach ($this->loanRepository->findActiveByMember($member) as $activeLoan) {
            if ($this->overdueDays($activeLoan, $now) > $blockingOverdueDays) {
                throw new \DomainException('Prêt bloqué en raison d’un livre en retard de plus de 30 jours.');
            }
        }

        $durationDays = (int) ($this->policy['loan_duration_days'] ?? 21);
        $loan = new Loan($member, $copy, $now, $now->modify(\sprintf('+%d days', $durationDays)));
        $copy->setStatus('emprunté');

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }

    public function renew(Loan $loan, ?\DateTimeImmutable $now = null): Loan
    {
        if (Loan::STATUS_RETURNED === $loan->getStatus()) {
            throw new \DomainException('Un emprunt déjà rendu ne peut pas être prolongé.');
        }

        $maxRenewals = (int) ($this->policy['max_renewals'] ?? 1);
        if ($loan->getRenewalsCount() >= $maxRenewals) {
            throw new \DomainException('Le nombre maximum de prolongations est atteint.');
        }

        if ($this->reservationRepository->hasActiveReservationForBook($loan->getCopy()->getBook())) {
            throw new \DomainException('Prolongation refusée : une réservation est en attente pour ce livre.');
        }

        $renewalDays = (int) ($this->policy['renewal_duration_days'] ?? 14);
        $loan->setDueAt($loan->getDueAt()->modify(\sprintf('+%d days', $renewalDays)));
        $loan->incrementRenewalsCount();

        $this->entityManager->flush();

        return $loan;
    }

    public function returnCopy(Loan $loan, ?\DateTimeImmutable $now = null): Loan
    {
        $now ??= new \DateTimeImmutable();

        if (Loan::STATUS_RETURNED === $loan->getStatus()) {
            throw new \DomainException('Cet emprunt est déjà clôturé.');
        }

        $loan->setReturnedAt($now);
        $loan->setStatus(Loan::STATUS_RETURNED);
        $loan->getCopy()->setStatus('disponible');

        $penaltyCents = $this->calculatePenaltyCents($loan, $now);
        if ($penaltyCents > 0) {
            $penalty = new Penalty($loan, $penaltyCents, $now);
            $loan->addPenalty($penalty);
            $this->entityManager->persist($penalty);
        }

        $this->entityManager->flush();

        return $loan;
    }

    public function calculatePenaltyCents(Loan $loan, \DateTimeImmutable $at): int
    {
        $overdueDays = $this->overdueDays($loan, $at);
        if ($overdueDays <= 0) {
            return 0;
        }

        $dailyCents = (int) round(((float) ($this->policy['daily_penalty_eur'] ?? 0.20)) * 100);
        $capCents = (int) round(((float) ($this->policy['max_penalty_per_book_eur'] ?? 10.0)) * 100);

        return min($capCents, $overdueDays * $dailyCents);
    }

    private function overdueDays(Loan $loan, \DateTimeImmutable $at): int
    {
        if ($at <= $loan->getDueAt()) {
            return 0;
        }

        return (int) $loan->getDueAt()->diff($at)->days;
    }
}
