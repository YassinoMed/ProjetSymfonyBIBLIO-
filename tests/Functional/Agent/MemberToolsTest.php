<?php

declare(strict_types=1);

namespace App\Tests\Functional\Agent;

use App\Agent\Tool\AgentToolRegistry;
use App\Entity\Book;
use App\Entity\Copy;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class MemberToolsTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private AgentToolRegistry $registry;

    private TokenStorageInterface $tokenStorage;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->registry = $container->get(AgentToolRegistry::class);
        $this->tokenStorage = $container->get(TokenStorageInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Penalty p')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Loan l')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Reservation r')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Copy c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Book b')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Member m')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->clear();

        $hasher = $container->get(UserPasswordHasherInterface::class);

        $this->alice = (new User())->setEmail('alice@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $this->alice->setPassword($hasher->hashPassword($this->alice, 'test-password'));

        $this->bob = (new User())->setEmail('bob@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $this->bob->setPassword($hasher->hashPassword($this->bob, 'test-password'));

        $aliceMember = (new Member('MEM-A', 'Alice', new \DateTimeImmutable('+1 year')))->setUser($this->alice);
        $bobMember = (new Member('MEM-B', 'Bob', new \DateTimeImmutable('+1 year')))->setUser($this->bob);

        $bookA = (new Book())->setTitle('Livre Alice')->setPublicationYear(2020);
        $bookB = (new Book())->setTitle('Livre Bob')->setPublicationYear(2021);
        $copyA = (new Copy())->setBarcode('BC-ALICE')->setStatus('emprunté')->setCondition('bon')->setBook($bookA);
        $copyB = (new Copy())->setBarcode('BC-BOB')->setStatus('emprunté')->setCondition('bon')->setBook($bookB);

        $loanA = new Loan($aliceMember, $copyA, new \DateTimeImmutable('-2 days'), new \DateTimeImmutable('+19 days'));
        $loanB = new Loan($bobMember, $copyB, new \DateTimeImmutable('-4 days'), new \DateTimeImmutable('+17 days'));

        $resA = (new Reservation())->setMember($aliceMember)->setBook($bookB)->setPosition(1);
        $resB = (new Reservation())->setMember($bobMember)->setBook($bookA)->setPosition(1);

        foreach ([$this->alice, $this->bob, $aliceMember, $bobMember, $bookA, $bookB, $copyA, $copyB, $loanA, $loanB, $resA, $resB] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testGetMyLoansReturnsOnlyConnectedMemberLoansAndRejectsUserIdParameter(): void
    {
        $alice = $this->reloadUser('alice@bibliotheque.test');
        $this->tokenStorage->setToken(new UsernamePasswordToken($alice, 'main', $alice->getRoles()));

        $result = $this->registry->execute('get_my_loans', [], $alice);

        self::assertSame(1, $result['count']);
        self::assertSame('Livre Alice', $result['loans'][0]['bookTitle']);

        $this->expectException(\InvalidArgumentException::class);
        $this->registry->execute('get_my_loans', ['userId' => $this->bob->getId()], $alice);
    }

    public function testGetMyReservationsReturnsOnlyConnectedMemberReservationsAndRejectsMemberIdParameter(): void
    {
        $alice = $this->reloadUser('alice@bibliotheque.test');
        $this->tokenStorage->setToken(new UsernamePasswordToken($alice, 'main', $alice->getRoles()));

        $result = $this->registry->execute('get_my_reservations', [], $alice);

        self::assertSame(1, $result['count']);
        self::assertSame('Livre Bob', $result['reservations'][0]['bookTitle']);

        $this->expectException(\InvalidArgumentException::class);
        $this->registry->execute('get_my_reservations', ['memberId' => 2], $alice);
    }

    private function reloadUser(string $email): User
    {
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        return $user;
    }
}
