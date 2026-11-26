<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PenaltyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PenaltyRepository::class)]
class Penalty
{
    public const string STATUS_DUE = 'due';
    public const string STATUS_PAID = 'payée';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'penalties')]
    #[ORM\JoinColumn(nullable: false)]
    private Loan $loan;

    #[ORM\Column]
    private int $amountCents;

    #[ORM\Column(length: 16)]
    private string $status = self::STATUS_DUE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Loan $loan,
        int $amountCents,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->loan = $loan;
        $this->amountCents = max(0, $amountCents);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoan(): Loan
    {
        return $this->loan;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getAmountEur(): float
    {
        return $this->amountCents / 100;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
