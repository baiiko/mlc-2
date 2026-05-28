<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\RoundMapRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Championship\Repository\SeasonRepositoryInterface;
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
        private RoundRepositoryInterface $roundRepository,
        private SeasonRepositoryInterface $seasonRepository,
    ) {
    }

    #[Route('/championship/maps', name: 'app_championship_maps', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $this->nonEmptyString($request, 'q');
        $author = $this->nonEmptyString($request, 'author');
        $roundId = $request->query->getInt('round', 0) ?: null;
        $seasonId = $request->query->getInt('season', 0) ?: null;

        $total = $this->roundMapRepository->countAll($search, $roundId, $seasonId, $author);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $maps = $this->roundMapRepository->findPaginated(
            $page,
            self::PER_PAGE,
            $search,
            $roundId,
            $seasonId,
            $author,
        );

        return new Response(
            $this->twig->render('championship/map/list.html.twig', [
                'maps' => $maps,
                'total' => $total,
                'page' => $page,
                'totalPages' => $totalPages,
                'perPage' => self::PER_PAGE,
                'search' => $search,
                'author' => $author,
                'roundId' => $roundId,
                'seasonId' => $seasonId,
                'rounds' => $this->roundRepository->findAllOrderedRecent(),
                'seasons' => $this->seasonRepository->findAll(),
            ])
        );
    }

    private function nonEmptyString(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query->get($key, ''));

        return $value === '' ? null : $value;
    }
}
