<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Book;
use App\Entity\Copy;
use App\Entity\Loan;
use App\Entity\Member;
use App\Entity\Reservation;
use App\Entity\User;
use App\Security\Voter\LoanVoter;
use App\Security\Voter\ReservationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class LoanAndReservationVoterTest extends TestCase
{
    public function testLoanVoterRestrictsToOwnerOrStaff(): void
    {
        $ownerUser = (new User())->setEmail('owner@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $otherUser = (new User())->setEmail('other@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $librarian = (new User())->setEmail('lib@bibliotheque.test')->setRoles(['ROLE_LIBRARIAN']);

        $member = (new Member('MEM-1', 'Owner', new \DateTimeImmutable('+1 year')))->setUser($ownerUser);
        $copy = (new Copy())->setBarcode('BC-1')->setStatus('emprunté')->setCondition('bon')->setBook(new Book());
        $loan = new Loan($member, $copy, new \DateTimeImmutable('-2 days'), new \DateTimeImmutable('+19 days'));

        $voter = new LoanVoter();

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($ownerUser), $loan, [LoanVoter::VIEW]),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($ownerUser), $loan, [LoanVoter::EXTEND]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($otherUser), $loan, [LoanVoter::VIEW]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($ownerUser), $loan, [LoanVoter::RETURN]),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($librarian), $loan, [LoanVoter::VIEW]),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($librarian), $loan, [LoanVoter::RETURN]),
        );
    }

    public function testReservationVoterRestrictsToOwnerOrStaff(): void
    {
        $ownerUser = (new User())->setEmail('owner@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $otherUser = (new User())->setEmail('other@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $librarian = (new User())->setEmail('lib@bibliotheque.test')->setRoles(['ROLE_LIBRARIAN']);

        $member = (new Member('MEM-1', 'Owner', new \DateTimeImmutable('+1 year')))->setUser($ownerUser);
        $reservation = (new Reservation())
            ->setMember($member)
            ->setBook(new Book())
            ->setPosition(1);

        $voter = new ReservationVoter();

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($ownerUser), $reservation, [ReservationVoter::VIEW]),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($ownerUser), $reservation, [ReservationVoter::CANCEL]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($otherUser), $reservation, [ReservationVoter::VIEW]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token($otherUser), $reservation, [ReservationVoter::CANCEL]),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token($librarian), $reservation, [ReservationVoter::VIEW]),
        );
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }
}
