<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Copy;
use App\Form\BookType;
use App\Form\CopyType;
use App\Security\Voter\BookVoter;
use App\Service\CatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_LIBRARIAN')]
final class CatalogManagementController extends AbstractController
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {
    }

    #[Route('/bibliothecaire/catalogue', name: 'app_librarian_catalog', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('librarian/catalog.html.twig', [
            'role' => 'librarian',
            'roleLabel' => 'Gestion du catalogue',
            'books' => $this->catalogService->listAllBooks(),
        ]);
    }

    #[Route('/bibliothecaire/catalogue/nouveau', name: 'app_librarian_book_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $book = new Book();
        $this->denyAccessUnlessGranted(BookVoter::MANAGE, $book);

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->catalogService->saveBook($book);
            $this->addFlash('success', 'Livre ajouté au catalogue.');

            return $this->redirectToRoute('app_librarian_book_edit', ['id' => $book->getId()]);
        }

        return $this->render('librarian/book_form.html.twig', [
            'role' => 'librarian',
            'roleLabel' => 'Nouveau livre',
            'book' => $book,
            'form' => $form,
            'copyForm' => null,
        ]);
    }

    #[Route('/bibliothecaire/catalogue/{id}/modifier', name: 'app_librarian_book_edit', methods: ['GET', 'POST'])]
    public function edit(Book $book, Request $request): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::MANAGE, $book);

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->catalogService->saveBook($book);
            $this->addFlash('success', 'Livre mis à jour.');

            return $this->redirectToRoute('app_librarian_book_edit', ['id' => $book->getId()]);
        }

        $copyForm = $this->createForm(CopyType::class, new Copy(), [
            'action' => $this->generateUrl('app_librarian_copy_add', ['id' => $book->getId()]),
        ]);

        return $this->render('librarian/book_form.html.twig', [
            'role' => 'librarian',
            'roleLabel' => 'Modifier un livre',
            'book' => $book,
            'form' => $form,
            'copyForm' => $copyForm,
        ]);
    }

    #[Route('/bibliothecaire/catalogue/{id}/supprimer', name: 'app_librarian_book_delete', methods: ['POST'])]
    public function delete(Book $book, Request $request): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::MANAGE, $book);

        if (!$this->isCsrfTokenValid('delete_book_'.$book->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->catalogService->deleteBook($book);
            $this->addFlash('success', 'Livre retiré du catalogue.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_librarian_catalog');
    }

    #[Route('/bibliothecaire/catalogue/{id}/exemplaire', name: 'app_librarian_copy_add', methods: ['POST'])]
    public function addCopy(Book $book, Request $request): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::MANAGE, $book);

        $copy = new Copy();
        $copyForm = $this->createForm(CopyType::class, $copy);
        $copyForm->handleRequest($request);

        if ($copyForm->isSubmitted() && $copyForm->isValid()) {
            $this->catalogService->addCopy($book, $copy);
            $this->addFlash('success', 'Exemplaire ajouté.');
        }

        return $this->redirectToRoute('app_librarian_book_edit', ['id' => $book->getId()]);
    }

    #[Route('/bibliothecaire/exemplaire/{id}/supprimer', name: 'app_librarian_copy_delete', methods: ['POST'])]
    public function deleteCopy(Copy $copy, Request $request): Response
    {
        $book = $copy->getBook();
        $this->denyAccessUnlessGranted(BookVoter::MANAGE, $book);

        if (!$this->isCsrfTokenValid('delete_copy_'.$copy->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->catalogService->removeCopy($copy);
            $this->addFlash('success', 'Exemplaire retiré.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_librarian_book_edit', ['id' => $book->getId()]);
    }
}
