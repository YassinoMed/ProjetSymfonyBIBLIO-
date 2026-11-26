<?php

declare(strict_types=1);

namespace App\Service;

final class DemoLibraryData
{
    /** @var list<array<string, bool|int|string>> */
    public const BOOKS = [
        [
            'id' => 1,
            'title' => 'Le Jardin des latitudes',
            'author' => 'Sofia Samatar',
            'year' => 2021,
            'category' => 'Imaginaire',
            'status' => 'available',
            'statusLabel' => 'Disponible',
            'copies' => 3,
            'accent' => 'ochre',
            'isbn' => '978-2-35178-621-4',
            'summary' => 'Un voyage contemplatif où la géographie devient langage, mémoire et promesse de refuge.',
        ],
        [
            'id' => 2,
            'title' => '我记得末日的蓝色',
            'author' => '阿来',
            'year' => 2018,
            'category' => 'Littérature',
            'status' => 'loan',
            'statusLabel' => 'Emprunté',
            'copies' => 2,
            'accent' => 'cobalt',
            'isbn' => '978-2-07-040829-2',
            'summary' => 'Une chronique familiale où le paysage du Sichuan devient le théâtre d’une mémoire familiale.',
        ],
        [
            'id' => 3,
            'title' => 'Habiter le regard',
            'author' => 'Mireille Calle-Gruber',
            'year' => 2024,
            'category' => 'Essais',
            'status' => 'available',
            'statusLabel' => 'Disponible',
            'copies' => 1,
            'accent' => 'moss',
            'isbn' => '978-2-35178-784-8',
            'summary' => 'Essai sensible sur la façon de voir, la ville et les gestes qui nous apprennent à habiter.',
        ],
        [
            'id' => 4,
            'title' => 'Les Silences de la mer',
            'author' => 'Léonora Miano',
            'year' => 2020,
            'category' => 'Romans',
            'status' => 'reserved',
            'statusLabel' => 'Réservé',
            'copies' => 2,
            'accent' => 'ink',
            'isbn' => '978-2-33652-087-6',
            'summary' => 'Une saga familiale où les continents séparés se répondent et où l’exil devient transmission.',
        ],
        [
            'id' => 5,
            'title' => 'Atlas des constellations disparues',
            'author' => 'David M. Raup',
            'year' => 2012,
            'category' => 'Sciences',
            'status' => 'available',
            'statusLabel' => 'Disponible',
            'copies' => 4,
            'accent' => 'plum',
            'isbn' => '978-2-7611-5121-9',
            'summary' => 'Un voyage scientifique et visuel dans l’histoire des étoiles effacées du ciel terrestre.',
        ],
        [
            'id' => 6,
            'title' => '写给明天的信',
            'author' => '余华',
            'year' => 2023,
            'category' => 'Littérature',
            'status' => 'available',
            'statusLabel' => 'Disponible',
            'copies' => 5,
            'accent' => 'vermilion',
            'isbn' => '978-2-07-041273-1',
            'summary' => 'Des lettres comme des capsules de temps, entre tendresse, absurdité et amitié.',
        ],
        [
            'id' => 7,
            'title' => 'La ville derrière la vitre',
            'author' => 'Yanis Varoufakis',
            'year' => 2025,
            'category' => 'Société',
            'status' => 'repair',
            'statusLabel' => 'En réparation',
            'copies' => 1,
            'accent' => 'slate',
            'isbn' => '978-2-08-264419-6',
            'summary' => 'Une analyse stimulante de la ville numérique et de ses frontières invisibles.',
        ],
        [
            'id' => 8,
            'title' => 'Le Musée des heures perdues',
            'author' => 'Alberto Manguel',
            'year' => 2015,
            'category' => 'Essais',
            'status' => 'available',
            'statusLabel' => 'Disponible',
            'copies' => 2,
            'accent' => 'sand',
            'isbn' => '978-2-07-041816-2',
            'summary' => 'Une réflexion encyclopédique sur les livres, les bibliothèques et les façons de perdre le temps.',
        ],
    ];

    /** @var list<array<string, string>> */
    public const LOANS = [
        [
            'title' => 'Habiter le regard',
            'author' => 'Mireille Calle-Gruber',
            'dueDate' => '28 nov.',
            'daysLeft' => '3 jours',
            'status' => 'À échéance proche',
            'accent' => 'moss',
        ],
        [
            'title' => 'Les Silences de la mer',
            'author' => 'Léonora Miano',
            'dueDate' => '12 déc.',
            'daysLeft' => '17 jours',
            'status' => 'En cours',
            'accent' => 'ink',
        ],
        [
            'title' => '写给明天的信',
            'author' => '余华',
            'dueDate' => '04 janv.',
            'daysLeft' => '40 jours',
            'status' => 'En cours',
            'accent' => 'vermilion',
        ],
    ];

    /** @var list<array<string, string>> */
    public const RESERVATIONS = [
        [
            'title' => 'Atlas des constellations disparues',
            'author' => 'David M. Raup',
            'position' => '2ᵉ position',
            'readyDate' => 'Disponible depuis le 21 nov.',
            'status' => 'ready',
            'statusLabel' => 'Disponible',
        ],
        [
            'title' => 'La ville derrière la vitre',
            'author' => 'Yanis Varoufakis',
            'position' => '1ʳᵉ position',
            'readyDate' => 'Disponible le 03 déc.',
            'status' => 'waiting',
            'statusLabel' => 'En attente',
        ],
    ];

    /** @var list<array<string, string>> */
    public const STAFF_TASKS = [
        [
            'member' => 'Salma Ben Youssef',
            'action' => 'Retour en retard',
            'detail' => 'Le poids de la nuit',
            'age' => '12 jours',
            'tone' => 'danger',
        ],
        [
            'member' => 'Ilyes Kaci',
            'action' => 'Réservation à préparer',
            'detail' => 'La couleur de l’eau',
            'age' => 'Depuis 2 h',
            'tone' => 'warning',
        ],
        [
            'member' => 'Nour El Mansouri',
            'action' => 'Prolongation à valider',
            'detail' => 'Dune',
            'age' => 'Il y a 4 h',
            'tone' => 'neutral',
        ],
    ];

    /** @var list<array<string, string>> */
    public const AUDIT_EVENTS = [
        ['action' => 'Retour enregistré', 'actor' => 'Amine B.', 'target' => 'Ligne 00842', 'time' => 'Aujourd’hui · 10:42'],
        ['action' => 'Rôle modifié', 'actor' => 'Admin', 'target' => 'M. Bensaïd', 'time' => 'Aujourd’hui · 09:18'],
        ['action' => 'Prolongation confirmée', 'actor' => 'Agent IA', 'target' => 'Membre #1048', 'time' => 'Hier · 17:06'],
        ['action' => 'Exemplaire retiré', 'actor' => 'Sofia R.', 'target' => 'Copie #00917', 'time' => 'Hier · 14:31'],
    ];

    /** @var list<array<string, string|int|bool>> */
    public const MEMBERS = [
        ['name' => 'Yasmine Nasri', 'email' => 'membre@bibliotheque.test', 'loans' => 3, 'reservations' => 2, 'active' => true, 'lastVisit' => 'Aujourd’hui'],
        ['name' => 'Ilyes Kaci', 'email' => 'ilyes@exemple.fr', 'loans' => 1, 'reservations' => 0, 'active' => true, 'lastVisit' => 'Hier'],
        ['name' => 'Nour El Mansouri', 'email' => 'nour@exemple.fr', 'loans' => 2, 'reservations' => 1, 'active' => true, 'lastVisit' => '12 nov.'],
        ['name' => 'Salma Ben Youssef', 'email' => 'salma@exemple.fr', 'loans' => 4, 'reservations' => 0, 'active' => false, 'lastVisit' => '03 nov.'],
    ];

    /** @var list<array<string, string|bool>> */
    public const RULES = [
        ['name' => 'Durée maximale d’un prêt', 'description' => '21 jours pour un membre actif', 'enabled' => true],
        ['name' => 'Prolongation automatique', 'description' => 'Une prolongation de 14 jours si aucune réservation', 'enabled' => true],
        ['name' => 'Pénalité de retard', 'description' => ' Suspension progressive après 7 jours', 'enabled' => true],
        ['name' => 'Réservation simultanée', 'description' => 'Trois réservations actives maximum', 'enabled' => false],
    ];

    /** @return list<array<string, bool|int|string>> */
    public function books(): array
    {
        return self::BOOKS;
    }

    /**
     * @return list<array<string, bool|int|string>>
     */
    public function filterBooks(?string $query = null, ?string $category = null, ?string $status = null, string $sort = 'title'): array
    {
        $normalizedQuery = $this->normalize($query ?? '');

        $books = array_values(array_filter(
            $this->books(),
            function (array $book) use ($normalizedQuery, $category, $status): bool {
                $haystack = $this->normalize(sprintf(
                    '%s %s %s %s',
                    $book['title'],
                    $book['author'],
                    $book['category'],
                    $book['summary'],
                ));

                if ($normalizedQuery !== '' && !str_contains($haystack, $normalizedQuery)) {
                    return false;
                }

                if ($category !== null && $category !== '' && $book['category'] !== $category) {
                    return false;
                }

                return $status === null || $status === '' || $book['status'] === $status;
            },
        ));

        usort($books, function (array $left, array $right) use ($sort): int {
            return match ($sort) {
                'year' => $right['year'] <=> $left['year'],
                'availability' => $left['statusLabel'] <=> $right['statusLabel'],
                default => $this->normalize((string) $left['title']) <=> $this->normalize((string) $right['title']),
            };
        });

        return $books;
    }

    /** @return list<string> */
    public function categories(): array
    {
        $categories = array_unique(array_column(self::BOOKS, 'category'));
        sort($categories);

        return $categories;
    }

    /** @return list<array<string, string>> */
    public function loans(): array
    {
        return self::LOANS;
    }

    /** @return list<array<string, string>> */
    public function reservations(): array
    {
        return self::RESERVATIONS;
    }

    /** @return list<array<string, string>> */
    public function staffTasks(): array
    {
        return self::STAFF_TASKS;
    }

    /** @return list<array<string, string>> */
    public function auditEvents(): array
    {
        return self::AUDIT_EVENTS;
    }

    /** @return list<array<string, string|int|bool>> */
    public function members(): array
    {
        return self::MEMBERS;
    }

    /** @return list<array<string, string|bool>> */
    public function rules(): array
    {
        return self::RULES;
    }

    private function normalize(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($value), 'UTF-8')
            : strtolower(trim($value));
    }
}
