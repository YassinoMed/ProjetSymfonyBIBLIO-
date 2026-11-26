<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PromptRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le prompt ne peut pas être vide.')]
        #[Assert\Length(
            min: 3,
            max: 2000,
            minMessage: 'Le prompt doit contenir au moins {{ limit }} caractères.',
            maxMessage: 'Le prompt ne doit pas dépasser {{ limit }} caractères.',
        )]
        public string $prompt,
    ) {
    }
}
