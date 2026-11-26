<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Loan;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, Loan>
 */
final class LoanVoter extends Voter
{
    use StaffAndOwnerTrait;

    public const string VIEW = 'LOAN_VIEW';
    public const string EXTEND = 'LOAN_EXTEND';
    public const string RETURN = 'LOAN_RETURN';
    public const string CREATE = 'LOAN_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EXTEND, self::RETURN, self::CREATE], true)
            && $subject instanceof Loan;
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

        if (\in_array($attribute, [self::CREATE, self::RETURN], true)) {
            return false;
        }

        return $this->isMemberOwner($subject->getMember(), $user);
    }
}
