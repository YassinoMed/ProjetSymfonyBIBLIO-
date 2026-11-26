<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class GetPolicyTool implements AgentToolInterface
{
    /**
     * @param array<string, int|float|string|bool> $policy
     */
    public function __construct(
        #[Autowire('%app.policy%')]
        private array $policy,
    ) {
    }

    public function getName(): string
    {
        return 'get_policy';
    }

    public function getDescription(): string
    {
        return 'Retourne les règles de prêt, prolongation, pénalité et réservation en vigueur.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, UserInterface $user): array
    {
        return [
            'rules' => $this->policy,
        ];
    }
}
