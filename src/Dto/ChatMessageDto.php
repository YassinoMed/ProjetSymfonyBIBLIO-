<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChatMessageDto
{
    public function __construct(
        #[Assert\Choice(choices: ['user', 'assistant'], message: 'Le rôle du message doit être "user" ou "assistant".')]
        public string $role,
        #[Assert\NotBlank(message: 'Le contenu du message historique ne peut pas être vide.', normalizer: 'trim')]
        #[Assert\Length(max: 2000, maxMessage: 'Un message historique ne doit pas dépasser {{ limit }} caractères.')]
        public string $content,
    ) {
    }
}
