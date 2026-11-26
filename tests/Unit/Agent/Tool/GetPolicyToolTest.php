<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent\Tool;

use App\Agent\Tool\GetPolicyTool;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class GetPolicyToolTest extends TestCase
{
    public function testItReturnsConfiguredPolicyParameters(): void
    {
        $configuredRules = [
            'loan_duration_days' => 21,
            'max_active_loans' => 5,
            'max_renewals' => 1,
            'renewal_duration_days' => 14,
            'daily_penalty_eur' => 0.20,
            'max_penalty_per_book_eur' => 10.0,
            'blocking_penalty_threshold_eur' => 10.0,
            'blocking_overdue_days' => 30,
            'max_reservations' => 3,
            'reservation_pickup_days' => 5,
        ];

        $tool = new GetPolicyTool($configuredRules);
        $user = (new User())->setEmail('membre@bibliotheque.test')->setRoles(['ROLE_MEMBER']);

        self::assertSame('get_policy', $tool->getName());
        self::assertSame(['rules' => $configuredRules], $tool->execute([], $user));
    }
}
