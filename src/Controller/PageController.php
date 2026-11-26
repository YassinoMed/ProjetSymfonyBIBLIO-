<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CatalogFilter;
use App\Service\DemoLibraryData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PageController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/catalogue', name: 'app_catalog', methods: ['GET'])]
    public function catalog(
        DemoLibraryData $demoData,
        #[MapQueryString] CatalogFilter $filter = new CatalogFilter(),
    ): Response {
        return $this->render('catalog/index.html.twig', [
            'books' => $demoData->filterBooks(
                $filter->query(),
                $filter->selectedCategory(),
                $filter->selectedStatus(),
                $filter->selectedSort(),
            ),
            'categories' => $demoData->categories(),
            'query' => $filter->query(),
            'selectedCategory' => $filter->selectedCategory(),
            'selectedStatus' => $filter->selectedStatus(),
            'selectedSort' => $filter->selectedSort(),
        ]);
    }

    #[Route('/membre', name: 'app_member', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBER')]
    public function member(DemoLibraryData $demoData): Response
    {
        return $this->render('member/index.html.twig', [
            'role' => 'member',
            'roleLabel' => 'Espace membre',
            'loans' => $demoData->loans(),
            'reservations' => $demoData->reservations(),
        ]);
    }

    #[Route('/bibliothecaire', name: 'app_librarian', methods: ['GET'])]
    #[IsGranted('ROLE_LIBRARIAN')]
    public function librarian(DemoLibraryData $demoData): Response
    {
        return $this->render('librarian/index.html.twig', [
            'role' => 'librarian',
            'roleLabel' => 'Espace bibliothécaire',
            'tasks' => $demoData->staffTasks(),
            'members' => $demoData->members(),
        ]);
    }

    #[Route('/administrateur', name: 'app_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function administrator(DemoLibraryData $demoData): Response
    {
        return $this->render('admin/index.html.twig', [
            'role' => 'admin',
            'roleLabel' => 'Administration',
            'members' => $demoData->members(),
            'rules' => $demoData->rules(),
            'auditEvents' => $demoData->auditEvents(),
        ]);
    }
}
