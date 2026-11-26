<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatConversation;
use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatConversationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ChatHistoryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChatConversationRepository $conversationRepository,
    ) {
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function getMessagesForUser(User $user): array
    {
        $conversation = $this->conversationRepository->findLatestByUser($user);
        if (null === $conversation) {
            return [];
        }

        $items = [];
        foreach ($conversation->getMessages() as $message) {
            $items[] = [
                'role' => $message->getRole(),
                'content' => $message->getContent(),
            ];
        }

        return $items;
    }

    public function appendExchange(User $user, string $userMessage, string $assistantResponse): void
    {
        $conversation = $this->conversationRepository->findLatestByUser($user);
        if (null === $conversation) {
            $conversation = new ChatConversation($user);
            $this->entityManager->persist($conversation);
        }

        $now = new \DateTimeImmutable();
        $conversation->addMessage(new ChatMessage($conversation, 'user', trim($userMessage), $now));
        $conversation->addMessage(new ChatMessage($conversation, 'assistant', trim($assistantResponse), $now->modify('+1 second')));

        $this->entityManager->flush();
    }

    public function clearForUser(User $user): void
    {
        $conversations = $this->conversationRepository->findBy(['user' => $user]);

        foreach ($conversations as $conversation) {
            $this->entityManager->remove($conversation);
        }

        if ([] !== $conversations) {
            $this->entityManager->flush();
        }
    }
}
