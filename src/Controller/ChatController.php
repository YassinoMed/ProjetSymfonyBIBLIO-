<?php

declare(strict_types=1);

namespace App\Controller;

use App\Agent\AgentService;
use App\Dto\ChatRequestDto;
use App\Entity\User;
use App\Exception\LlmInvalidResponseException;
use App\Exception\LlmUnauthorizedException;
use App\Exception\LlmUnavailableException;
use App\Service\ChatHistoryService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChatController extends AbstractController
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly ChatHistoryService $chatHistoryService,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'limiter.agent_chat')]
        private readonly RateLimiterFactory $agentChatLimiter,
    ) {
    }

    #[Route('/assistant', name: 'app_chat_ui', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBER')]
    public function index(): Response
    {
        $user = $this->getUser();
        $history = $user instanceof User ? $this->chatHistoryService->getMessagesForUser($user) : [];

        return $this->render('chat/index.html.twig', [
            'role' => $this->isGranted('ROLE_ADMIN') ? 'admin' : ($this->isGranted('ROLE_LIBRARIAN') ? 'librarian' : 'member'),
            'roleLabel' => 'Assistant IA',
            'history' => $history,
        ]);
    }

    #[Route('/chat/history', name: 'app_chat_history', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBER')]
    public function history(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'messages' => $user instanceof User ? $this->chatHistoryService->getMessagesForUser($user) : [],
        ]);
    }

    #[Route('/chat/reset', name: 'app_chat_reset', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBER')]
    public function reset(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('chat', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(
                ['error' => 'Jeton de sécurité invalide. Rechargez la page.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            $this->chatHistoryService->clearForUser($user);
        }

        return $this->json(['status' => 'cleared']);
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

        $limiter = $this->agentChatLimiter->create(
            $this->getUser()?->getUserIdentifier() ?? $request->getClientIp() ?? 'anonymous',
        );
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez patienter avant de réessayer.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $retryAfter],
            );
        }

        $user = $this->getUser();
        $history = array_map(
            static fn ($entry): array => ['role' => $entry->role, 'content' => $entry->content],
            $dto->history,
        );

        if ([] === $history && $user instanceof User) {
            $history = $this->chatHistoryService->getMessagesForUser($user);
        }

        try {
            $answer = $this->agentService->reply($dto->message, $history, $user);
        } catch (LlmUnavailableException|LlmUnauthorizedException|LlmInvalidResponseException $e) {
            $this->logger->warning('Agent LLM unavailable.', ['exception' => $e::class]);

            return $this->json(
                ['error' => 'L’assistant IA est momentanément indisponible. Vous pouvez continuer à utiliser la bibliothèque normalement.'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected agent chat failure.', ['exception' => $e::class]);

            return $this->json(
                ['error' => 'L’assistant IA est momentanément indisponible. Vous pouvez continuer à utiliser la bibliothèque normalement.'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        if ($user instanceof User) {
            $this->chatHistoryService->appendExchange($user, $dto->message, $answer);
        }

        return $this->json([
            'status' => 'success',
            'response' => htmlspecialchars($answer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
    }
}
