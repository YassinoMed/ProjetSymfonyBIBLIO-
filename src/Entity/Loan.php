<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Index(name: 'idx_loan_status', columns: ['status'])]
class Loan
{
    public const string STATUS_ACTIVE = 'en_cours';
    public const string STATUS_RETURNED = 'rendu';
    public const string STATUS_OVERDUE = 'en_retard';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private Member $member;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Copy $copy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dueAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column]
    private int $renewalsCount = 0;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    /**
     * @var Collection<int, Penalty>
     */
    #[ORM\OneToMany(mappedBy: 'loan', targetEntity: Penalty::class, cascade: ['persist'])]
    private Collection $penalties;

    public function __construct(
        Member $member,
        Copy $copy,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $dueAt,
    ) {
        $this->member = $member;
        $this->copy = $copy;
        $this->startedAt = $startedAt;
        $this->dueAt = $dueAt;
        $this->penalties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getCopy(): Copy
    {
        return $this->copy;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getDueAt(): \DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(\DateTimeImmutable $dueAt): self
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): self
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }

    public function getRenewalsCount(): int
    {
        return $this->renewalsCount;
    }

    public function incrementRenewalsCount(): self
    {
        ++$this->renewalsCount;

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

    /**
     * @return Collection<int, Penalty>
     */
    public function getPenalties(): Collection
    {
        return $this->penalties;
    }

    public function addPenalty(Penalty $penalty): self
    {
        if (!$this->penalties->contains($penalty)) {
            $this->penalties->add($penalty);
        }

        return $this;
    }
}
