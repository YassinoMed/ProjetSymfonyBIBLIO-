<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class RoleHierarchyTest extends KernelTestCase
{
    public function testAdminInheritsLibrarianAndMemberRoles(): void
    {
        self::bootKernel();

        $hierarchy = self::getContainer()->get(RoleHierarchyInterface::class);
        self::assertInstanceOf(RoleHierarchyInterface::class, $hierarchy);

        $roles = $hierarchy->getReachableRoleNames(['ROLE_ADMIN']);

        self::assertContains('ROLE_LIBRARIAN', $roles);
        self::assertContains('ROLE_MEMBER', $roles);
    }

    public function testMemberDoesNotInheritLibrarianRole(): void
    {
        self::bootKernel();

        $hierarchy = self::getContainer()->get(RoleHierarchyInterface::class);
        self::assertInstanceOf(RoleHierarchyInterface::class, $hierarchy);

        $roles = $hierarchy->getReachableRoleNames(['ROLE_MEMBER']);

        self::assertNotContains('ROLE_LIBRARIAN', $roles);
        self::assertNotContains('ROLE_ADMIN', $roles);
    }
}
