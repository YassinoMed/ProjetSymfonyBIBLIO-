<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent;

use App\Agent\Tool\AgentToolInterface;
use App\Agent\Tool\AgentToolRegistry;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class AgentToolRegistryTest extends TestCase
{
    public function testItExposesDefinitionsAndExecutesToolWithCurrentUser(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);

        $tool = new class () implements AgentToolInterface {
            public function getName(): string
            {
                return 'echo_user';
            }

            public function getDescription(): string
            {
                return 'Retourne les arguments et l’identifiant utilisateur.';
            }

            public function getParametersSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                    ],
                    'required' => ['query'],
                ];
            }

            public function execute(array $arguments, UserInterface $user): array
            {
                return [
                    'actor' => $user->getUserIdentifier(),
                    'arguments' => $arguments,
                ];
            }
        };

        $registry = new AgentToolRegistry([$tool]);

        self::assertTrue($registry->has('echo_user'));
        self::assertFalse($registry->has('unknown_tool'));
        self::assertSame([
            [
                'name' => 'echo_user',
                'description' => 'Retourne les arguments et l’identifiant utilisateur.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ], $registry->getDefinitions());

        self::assertSame([
            'actor' => 'membre@bibliotheque.test',
            'arguments' => ['query' => 'Dune'],
        ], $registry->execute('echo_user', ['query' => 'Dune'], $user));
    }

    public function testItThrowsForAnUnknownTool(): void
    {
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);
        $registry = new AgentToolRegistry([]);

        $this->expectException(\InvalidArgumentException::class);

        $registry->execute('non_existent', [], $user);
    }

    public function testItRejectsDuplicateToolNames(): void
    {
        $toolA = $this->createDummyTool('duplicate');
        $toolB = $this->createDummyTool('duplicate');

        $this->expectException(\LogicException::class);

        new AgentToolRegistry([$toolA, $toolB]);
    }

    private function createDummyTool(string $name): AgentToolInterface
    {
        return new class ($name) implements AgentToolInterface {
            public function __construct(private readonly string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return 'Dummy';
            }

            public function getParametersSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function execute(array $arguments, UserInterface $user): array
            {
                return [];
            }
        };
    }
}
