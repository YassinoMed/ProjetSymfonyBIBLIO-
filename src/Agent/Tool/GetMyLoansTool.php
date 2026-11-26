<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Repository\LoanRepository;
use App\Security\Voter\LoanVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class GetMyLoansTool implements AgentToolInterface
{
    public function __construct(
        private ConnectedMemberResolver $memberResolver,
        private LoanRepository $loanRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getName(): string
    {
        return 'get_my_loans';
    }

    public function getDescription(): string
    {
        return 'Retourne les emprunts en cours du membre connecté uniquement.';
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
        $member = $this->memberResolver->resolve($arguments, $user);
        if (null === $member) {
            return ['count' => 0, 'loans' => []];
        }

        $loans = [];
        foreach ($this->loanRepository->findActiveByMember($member) as $loan) {
            if (!$this->authorizationChecker->isGranted(LoanVoter::VIEW, $loan)) {
                continue;
            }

            $copy = $loan->getCopy();
            $loans[] = [
                'loanId' => $loan->getId(),
                'bookTitle' => $copy->getBook()->getTitle(),
                'barcode' => $copy->getBarcode(),
                'startedAt' => $loan->getStartedAt()->format('Y-m-d'),
                'dueAt' => $loan->getDueAt()->format('Y-m-d'),
                'status' => $loan->getStatus(),
                'renewalsCount' => $loan->getRenewalsCount(),
            ];
        }

        return [
            'count' => \count($loans),
            'loans' => $loans,
        ];
    }
}
