<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Entity\Member;
use App\Entity\User;
use App\Repository\MemberRepository;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class ConnectedMemberResolver
{
    public function __construct(
        private MemberRepository $memberRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function resolve(array $arguments, UserInterface $user): ?Member
    {
        if ([] !== $arguments) {
            throw new \InvalidArgumentException('This tool does not accept any user or member identifier.');
        }

        if (!$user instanceof User) {
            return null;
        }

        return $this->memberRepository->findOneBy(['user' => $user]);
    }
}
