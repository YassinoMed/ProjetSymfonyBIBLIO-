<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Copy;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const string DEMO_PASSWORD = 'demo1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create 5 categories (English names)
        $catNames = ['Romance', 'Science Fiction', 'Mystery', 'Fantasy', 'Non-fiction'];
        $categories = [];
        foreach ($catNames as $catName) {
            $category = new Category();
            $category->setName($catName);
            $manager->persist($category);
            $categories[] = $category;
        }

        // Create 5 authors
        $authorsData = [
            ['name' => 'Austen', 'firstname' => 'Jane'],
            ['name' => 'Asimov', 'firstname' => 'Isaac'],
            ['name' => 'Christie', 'firstname' => 'Agatha'],
            ['name' => 'Tolkien', 'firstname' => 'JRR'],
            ['name' => 'King', 'firstname' => 'Stephen'],
        ];
        $authors = [];
        foreach ($authorsData as $ad) {
            $author = new Author();
            $author->setName($ad['name']);
            $author->setFirstname($ad['firstname']);
            $manager->persist($author);
            $authors[] = $author;
        }

        // Create 5 books
        $bookInfos = [
            ['t' => 'Pride and Prejudice', 'i' => '9780141439518', 's' => 'Romance classic', 'y' => 1813, 'ci' => 0, 'ai' => 0],
            ['t' => 'Foundation', 'i' => '9780451450524', 's' => 'Sci-fi saga', 'y' => 1951, 'ci' => 1, 'ai' => 1],
            ['t' => 'Murder on Orient Express', 'i' => '9780062693718', 's' => 'Classic mystery', 'y' => 1934, 'ci' => 2, 'ai' => 2],
            ['t' => 'Lord of the Rings', 'i' => '9780261102385', 's' => 'Epic fantasy', 'y' => 1954, 'ci' => 3, 'ai' => 3],
            ['t' => 'The Stand', 'i' => '9780553295447', 's' => 'Post-apoc thriller', 'y' => 1978, 'ci' => 4, 'ai' => 4],
        ];
        $books = [];
        foreach ($bookInfos as $bi) {
            $book = new Book();
            $book->setTitle($bi['t']);
            $book->setIsbn($bi['i']);
            $book->setSummary($bi['s']);
            $book->setPublicationYear($bi['y']);
            $book->setCategory($categories[$bi['ci']]);
            $book->addAuthor($authors[$bi['ai']]);
            $manager->persist($book);
            $books[] = $book;
        }

        // Create 3 copies per book
        foreach ($bookInfos as $bi) {
            $book = $books[$bi['ai']];
            for ($j = 0; $j < 3; $j++) {
                $copy = new Copy();
                $copy->setBarcode('BARCODE_' . $bi['t'] . '_' . $j);
                $copy->setStatus($j === 0 ? 'available' : 'borrowed');
                $copy->setCondition('good');
                $copy->setBook($book);
                $manager->persist($copy);
            }
        }

        // Create test users for different roles
        $usersData = [
            ['email' => 'member@test.com', 'roles' => ['ROLE_MEMBER']],
            ['email' => 'bibliothecaire@test.com', 'roles' => ['ROLE_LIBRARIAN']],
            ['email' => 'admin@test.com', 'roles' => ['ROLE_ADMIN']],
        ];

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
