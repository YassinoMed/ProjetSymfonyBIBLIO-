<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const string DEMO_PASSWORD = 'demo1234';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var list<array{string, list<string>}> $users */
        $users = [
            ['membre@bibliotheque.test', ['ROLE_MEMBER']],
            ['bibliothecaire@bibliotheque.test', ['ROLE_LIBRARIAN']],
            ['admin@bibliotheque.test', ['ROLE_ADMIN']],
        ];

        foreach ($users as [$email, $roles]) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles($roles);
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
