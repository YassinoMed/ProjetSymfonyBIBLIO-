<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Reservation;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, Reservation>
 */
final class ReservationVoter extends Voter
{
    use StaffAndOwnerTrait;

    public const string VIEW = 'RESERVATION_VIEW';
    public const string CANCEL = 'RESERVATION_CANCEL';
    public const string CREATE = 'RESERVATION_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CANCEL, self::CREATE], true)
            && $subject instanceof Reservation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->isStaff($user)) {
            return true;
        }

        return $this->isMemberOwner($subject->getMember(), $user);
    }
}
