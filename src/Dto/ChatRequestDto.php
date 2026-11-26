<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChatRequestDto
{
    /**
     * @param list<ChatMessageDto> $history
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Le message ne peut pas être vide.', normalizer: 'trim')]
        #[Assert\Length(
            min: 1,
            max: 2000,
            minMessage: 'Le message doit contenir au moins {{ limit }} caractère.',
            maxMessage: 'Le message ne doit pas dépasser {{ limit }} caractères.',
        )]
        public string $message,
        #[Assert\Valid]
        #[Assert\Count(max: 50, maxMessage: 'L\'historique ne peut pas dépasser {{ limit }} messages.')]
        public array $history = [],
    ) {
    }
}
