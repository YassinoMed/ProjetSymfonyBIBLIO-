<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent;

use App\Agent\LlmClientInterface;
use App\Agent\OpenAiCompatibleLlmClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LlmClientContainerTest extends KernelTestCase
{
    public function testLlmClientInterfaceIsWiredToOpenAiCompatibleClient(): void
    {
        self::bootKernel();

        $client = self::getContainer()->get(LlmClientInterface::class);

        self::assertInstanceOf(OpenAiCompatibleLlmClient::class, $client);
    }
}
