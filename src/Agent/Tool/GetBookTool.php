<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Repository\BookRepository;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class GetBookTool implements AgentToolInterface
{
    public function __construct(
        private BookRepository $bookRepository,
        private BookCatalogFormatter $formatter,
    ) {
    }

    public function getName(): string
    {
        return 'get_book';
    }

    public function getDescription(): string
    {
        return 'Retourne la fiche détaillée d’un livre et l’état de ses exemplaires.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'bookId' => ['type' => 'integer', 'description' => 'Identifiant du livre.'],
            ],
            'required' => ['bookId'],
        ];
    }

    public function execute(array $arguments, UserInterface $user): array
    {
        $bookId = $arguments['bookId'] ?? null;
        if (!\is_int($bookId) || $bookId <= 0) {
            throw new \InvalidArgumentException('The "bookId" parameter must be a positive integer.');
        }

        $book = $this->bookRepository->find($bookId);
        if (null === $book) {
            return [
                'found' => false,
                'bookId' => $bookId,
            ];
        }

        return [
            'found' => true,
            'book' => $this->formatter->formatDetail($book),
        ];
    }
}
