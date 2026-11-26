<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_MEMBER_NUMBER', fields: ['memberNumber'])]
class Member
{
    public const string STATUS_ACTIVE = 'actif';
    public const string STATUS_SUSPENDED = 'suspendu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $memberNumber;

    #[ORM\Column(length: 180)]
    private string $fullName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $membershipExpiresAt;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\OneToOne(inversedBy: 'member', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(mappedBy: 'member', targetEntity: Loan::class)]
    private Collection $loans;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(mappedBy: 'member', targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct(
        string $memberNumber,
        string $fullName,
        \DateTimeImmutable $membershipExpiresAt,
    ) {
        $this->memberNumber = $memberNumber;
        $this->fullName = $fullName;
        $this->membershipExpiresAt = $membershipExpiresAt;
        $this->loans = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMemberNumber(): string
    {
        return $this->memberNumber;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getMembershipExpiresAt(): \DateTimeImmutable
    {
        return $this->membershipExpiresAt;
    }

    public function setMembershipExpiresAt(\DateTimeImmutable $membershipExpiresAt): self
    {
        $this->membershipExpiresAt = $membershipExpiresAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isActiveAt(\DateTimeImmutable $at): bool
    {
        return self::STATUS_ACTIVE === $this->status && $this->membershipExpiresAt >= $at;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }
}
