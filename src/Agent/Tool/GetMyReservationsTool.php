<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Repository\ReservationRepository;
use App\Security\Voter\ReservationVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class GetMyReservationsTool implements AgentToolInterface
{
    public function __construct(
        private ConnectedMemberResolver $memberResolver,
        private ReservationRepository $reservationRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getName(): string
    {
        return 'get_my_reservations';
    }

    public function getDescription(): string
    {
        return 'Retourne les réservations actives du membre connecté uniquement.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, UserInterface $user): array
    {
        $member = $this->memberResolver->resolve($arguments, $user);
        if (null === $member) {
            return ['count' => 0, 'reservations' => []];
        }

        $reservations = [];
        foreach ($this->reservationRepository->findActiveByMember($member) as $reservation) {
            if (!$this->authorizationChecker->isGranted(ReservationVoter::VIEW, $reservation)) {
                continue;
            }

            $reservations[] = [
                'reservationId' => $reservation->getId(),
                'bookTitle' => $reservation->getBook()?->getTitle(),
                'status' => $reservation->getStatus(),
                'position' => $reservation->getPosition(),
                'date' => $reservation->getDate()->format('Y-m-d'),
                'expiresAt' => $reservation->getExpiresAt()?->format('Y-m-d'),
            ];
        }

        return [
            'count' => \count($reservations),
            'reservations' => $reservations,
        ];
    }
}
