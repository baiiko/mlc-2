<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
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
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    #[Route('/championship/maps', name: 'app_championship_maps', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $page = max(1, $this->positiveInt($request, 'page') ?? 1);
        $search = $this->nonEmptyString($request, 'q');
        $author = $this->nonEmptyString($request, 'author');
        $roundId = $this->positiveInt($request, 'round');
        $seasonId = $this->positiveInt($request, 'season');

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

        $mapRecords = $this->mapRecordRepository->findBestRecordsByLapsForMapUids(
            array_filter(array_map(static fn ($m): ?string => $m->getUid(), $maps)),
        );

        return new Response(
            $this->twig->render('championship/map/list.html.twig', [
                'maps' => $maps,
                'mapRecords' => $mapRecords,
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

    /**
     * Read a strictly positive int from the query string, tolerating empty values
     * (Symfony's getInt() throws a 400 on empty strings).
     */
    private function positiveInt(Request $request, string $key): ?int
    {
        $value = trim((string) $request->query->get($key, ''));

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
