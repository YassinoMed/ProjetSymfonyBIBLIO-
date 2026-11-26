<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Security\Core\User\UserInterface;

final class AgentToolRegistry
{
    /**
     * @var array<string, AgentToolInterface>
     */
    private array $tools = [];

    /**
     * @param iterable<AgentToolInterface> $tools
     */
    public function __construct(
        #[AutowireIterator('app.agent_tool')]
        iterable $tools = [],
    ) {
        foreach ($tools as $tool) {
            $name = $tool->getName();

            if (isset($this->tools[$name])) {
                throw new \LogicException(\sprintf('Duplicate agent tool name "%s".', $name));
            }

            $this->tools[$name] = $tool;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    public function getDefinitions(): array
    {
        $definitions = [];

        foreach ($this->tools as $tool) {
            $definitions[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema(),
            ];
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function execute(string $name, array $arguments, UserInterface $user): array
    {
        if (!isset($this->tools[$name])) {
            throw new \InvalidArgumentException(\sprintf('Unknown agent tool "%s".', $name));
        }

        return $this->tools[$name]->execute($arguments, $user);
    }
}
