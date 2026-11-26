<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Repository\BookRepository;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SearchBooksTool implements AgentToolInterface
{
    public function __construct(
        private BookRepository $bookRepository,
        private BookCatalogFormatter $formatter,
    ) {
    }

    public function getName(): string
    {
        return 'search_books';
    }

    public function getDescription(): string
    {
        return 'Recherche des livres dans le catalogue par mot-clé, auteur, catégorie et disponibilité.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Terme de recherche (titre, résumé, ISBN ou auteur).'],
                'author' => ['type' => 'string', 'description' => 'Filtre sur le nom ou prénom de l’auteur.'],
                'category' => ['type' => 'string', 'description' => 'Filtre sur la catégorie.'],
                'availableOnly' => ['type' => 'boolean', 'description' => 'Limiter aux livres ayant au moins un exemplaire disponible.'],
                'limit' => ['type' => 'integer', 'description' => 'Nombre maximum de résultats (1 à 20).'],
            ],
        ];
    }

    public function execute(array $arguments, UserInterface $user): array
    {
        $query = isset($arguments['query']) && \is_string($arguments['query']) ? $arguments['query'] : null;
        $author = isset($arguments['author']) && \is_string($arguments['author']) ? $arguments['author'] : null;
        $category = isset($arguments['category']) && \is_string($arguments['category']) ? $arguments['category'] : null;
        $availableOnly = isset($arguments['availableOnly']) && \is_bool($arguments['availableOnly']) ? $arguments['availableOnly'] : false;
        $limit = isset($arguments['limit']) && \is_int($arguments['limit']) ? $arguments['limit'] : 10;

        $books = $this->bookRepository->searchCatalog($query, $author, $category, $availableOnly, $limit);

        $formatted = array_map(
            fn ($book): array => $this->formatter->formatSummary($book),
            $books,
        );

        return [
            'count' => \count($formatted),
            'books' => $formatted,
        ];
    }
}
