<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuditLoggerPersistenceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\AuditLog a')->execute();
        $entityManager->clear();
    }

    public function testAuditLogIsStoredInPostgreSql(): void
    {
        /** @var AuditLogger $auditLogger */
        $auditLogger = self::getContainer()->get(AuditLogger::class);
        /** @var AuditLogRepository $repository */
        $repository = self::getContainer()->get(AuditLogRepository::class);

        $created = $auditLogger->log(
            actor: 'admin@bibliotheque.test',
            tool: 'get_policy',
            parameters: ['section' => 'loans'],
            resultSummary: 'Règles retournées',
            origin: AuditLog::ORIGIN_UI,
        );

        self::assertNotNull($created->getId());

        $stored = $repository->find($created->getId());
        self::assertInstanceOf(AuditLog::class, $stored);
        self::assertSame('admin@bibliotheque.test', $stored->getActor());
        self::assertSame('get_policy', $stored->getTool());
        self::assertSame(['section' => 'loans'], $stored->getParameters());
        self::assertSame('Règles retournées', $stored->getResultSummary());
        self::assertSame(AuditLog::ORIGIN_UI, $stored->getOrigin());
    }
}
