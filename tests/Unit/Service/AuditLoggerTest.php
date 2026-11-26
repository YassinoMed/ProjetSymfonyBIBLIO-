<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\AuditLog;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AuditLoggerTest extends TestCase
{
    public function testItPersistsAndFlushesAnAuditLogEntry(): void
    {
        $persisted = [];
        $flushed = false;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager
            ->expects(self::once())
            ->method('flush')
            ->willReturnCallback(static function () use (&$flushed): void {
                $flushed = true;
            });

        $logger = new AuditLogger($entityManager);
        $entry = $logger->log(
            actor: 'membre@bibliotheque.test',
            tool: 'search_books',
            parameters: ['query' => 'Dune', 'availableOnly' => true],
            resultSummary: '3 livres trouvés',
            origin: AuditLog::ORIGIN_AGENT,
        );

        self::assertTrue($flushed);
        self::assertCount(1, $persisted);
        self::assertSame($entry, $persisted[0]);
        self::assertSame('membre@bibliotheque.test', $entry->getActor());
        self::assertSame('search_books', $entry->getTool());
        self::assertSame(['query' => 'Dune', 'availableOnly' => true], $entry->getParameters());
        self::assertSame('3 livres trouvés', $entry->getResultSummary());
        self::assertSame(AuditLog::ORIGIN_AGENT, $entry->getOrigin());
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $entry->getCreatedAt());
    }

    public function testItRejectsAnUnknownOrigin(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $logger = new AuditLogger($entityManager);

        $this->expectException(\InvalidArgumentException::class);

        $logger->log(
            actor: 'membre@bibliotheque.test',
            tool: 'search_books',
            parameters: [],
            resultSummary: 'ok',
            origin: 'cli',
        );
    }
}
