<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_log_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_audit_log_actor', columns: ['actor'])]
class AuditLog
{
    public const string ORIGIN_UI = 'UI';
    public const string ORIGIN_AGENT = 'agent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $actor;

    #[ORM\Column(length: 100)]
    private string $tool;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $parameters = [];

    #[ORM\Column(type: Types::TEXT)]
    private string $resultSummary;

    #[ORM\Column(length: 16)]
    private string $origin;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        string $actor,
        string $tool,
        array $parameters,
        string $resultSummary,
        string $origin,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        if (!\in_array($origin, [self::ORIGIN_UI, self::ORIGIN_AGENT], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid audit log origin "%s".', $origin));
        }

        $this->actor = $actor;
        $this->tool = $tool;
        $this->parameters = $parameters;
        $this->resultSummary = $resultSummary;
        $this->origin = $origin;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActor(): string
    {
        return $this->actor;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getResultSummary(): string
    {
        return $this->resultSummary;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
