<?php

declare(strict_types=1);

namespace App\Controller;

use App\Agent\AgentService;
use App\Dto\ChatRequestDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChatController extends AbstractController
{
    public function __construct(
        private readonly AgentService $agentService,
    ) {
    }

    #[Route('/chat', name: 'app_chat', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBER')]
    public function chat(Request $request, #[MapRequestPayload] ChatRequestDto $dto): JsonResponse
    {
        if (!$this->isCsrfTokenValid('chat', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(
                ['error' => 'Jeton de sécurité invalide. Rechargez la page.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $history = array_map(
            static fn ($entry): array => ['role' => $entry->role, 'content' => $entry->content],
            $dto->history,
        );

        $answer = $this->agentService->reply($dto->message, $history);

        return $this->json([
            'status' => 'success',
            'response' => htmlspecialchars($answer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
    }
}
