<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Member;
use Symfony\Component\Security\Core\User\UserInterface;

trait StaffAndOwnerTrait
{
    private function isStaff(UserInterface $user): bool
    {
        $roles = $user->getRoles();

        return \in_array('ROLE_LIBRARIAN', $roles, true) || \in_array('ROLE_ADMIN', $roles, true);
    }

    private function isMemberOwner(?Member $member, UserInterface $user): bool
    {
        $owner = $member?->getUser();

        return null !== $owner && $owner->getUserIdentifier() === $user->getUserIdentifier();
    }
}
