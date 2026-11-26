<?php

declare(strict_types=1);

namespace App\Agent\Tool;

use App\Entity\Book;

final readonly class BookCatalogFormatter
{
    private const array AVAILABLE_STATUSES = ['disponible', 'available'];

    /**
     * @return array{
     *     id: ?int,
     *     title: ?string,
     *     isbn: ?string,
     *     publicationYear: ?int,
     *     category: ?string,
     *     authors: list<string>,
     *     availableCopies: int,
     *     totalCopies: int,
     *     summary: ?string,
     * }
     */
    public function formatSummary(Book $book): array
    {
        $authors = [];
        foreach ($book->getAuthors() as $author) {
            $fullName = trim(\sprintf('%s %s', (string) $author->getFirstname(), (string) $author->getName()));
            if ('' !== $fullName) {
                $authors[] = $fullName;
            }
        }

        $availableCopies = 0;
        $totalCopies = 0;

        foreach ($book->getCopies() as $copy) {
            ++$totalCopies;
            if (\in_array($copy->getStatus(), self::AVAILABLE_STATUSES, true)) {
                ++$availableCopies;
            }
        }

        return [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'isbn' => $book->getIsbn(),
            'publicationYear' => $book->getPublicationYear(),
            'category' => $book->getCategory()?->getName(),
            'authors' => $authors,
            'availableCopies' => $availableCopies,
            'totalCopies' => $totalCopies,
            'summary' => $book->getSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatDetail(Book $book): array
    {
        $copies = [];
        foreach ($book->getCopies() as $copy) {
            $copies[] = [
                'barcode' => $copy->getBarcode(),
                'status' => $copy->getStatus(),
                'condition' => $copy->getCondition(),
            ];
        }

        return [
            ...$this->formatSummary($book),
            'copies' => $copies,
        ];
    }
}
