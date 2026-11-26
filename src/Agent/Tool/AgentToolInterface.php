<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\User\UserInterface;

#[AutoconfigureTag('app.agent_tool')]
interface AgentToolInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @return array<string, mixed>
     */
    public function getParametersSchema(): array;

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function execute(array $arguments, UserInterface $user): array;
}
