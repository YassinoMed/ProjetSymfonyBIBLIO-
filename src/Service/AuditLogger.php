<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function log(
        string $actor,
        string $tool,
        array $parameters,
        string $resultSummary,
        string $origin,
    ): AuditLog {
        $entry = new AuditLog(
            actor: $actor,
            tool: $tool,
            parameters: $parameters,
            resultSummary: $resultSummary,
            origin: $origin,
        );

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }
}
