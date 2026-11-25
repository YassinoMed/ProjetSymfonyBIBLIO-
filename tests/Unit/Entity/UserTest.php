<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailIsUsedAsUserIdentifier(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test');

        self::assertSame('membre@bibliotheque.test', $user->getUserIdentifier());
        self::assertSame('membre@bibliotheque.test', $user->getEmail());
    }

    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = (new User())->setRoles(['ROLE_MEMBER']);

        self::assertSame(['ROLE_MEMBER', 'ROLE_USER'], $user->getRoles());
    }

    public function testGetRolesDoesNotDuplicateRoleUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }
}
