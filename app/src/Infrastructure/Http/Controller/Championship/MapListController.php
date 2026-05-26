<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\RoundMapRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class MapListController
{
    private const PER_PAGE = 12;

    public function __construct(
        private Environment $twig,
        private RoundMapRepositoryInterface $roundMapRepository,
    ) {
    }

    #[Route('/championship/maps', name: 'app_championship_maps', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('q', ''));
        $search = $search === '' ? null : $search;

        $total = $this->roundMapRepository->countAll($search);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $maps = $this->roundMapRepository->findPaginated($page, self::PER_PAGE, $search);

        return new Response(
            $this->twig->render('championship/map/list.html.twig', [
                'maps' => $maps,
                'total' => $total,
                'page' => $page,
                'totalPages' => $totalPages,
                'perPage' => self::PER_PAGE,
                'search' => $search,
            ])
        );
    }
}
