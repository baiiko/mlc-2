<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Application\Championship\Service\CalculateRankingServiceInterface;
use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Repository\SeasonRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class SeasonShowController
{
    public function __construct(
        private Environment $twig,
        private SeasonRepositoryInterface $seasonRepository,
        private CalculateRankingServiceInterface $rankingService,
    ) {
    }

    #[Route('/championship/{slug}', name: 'app_championship_season_show', methods: ['GET'])]
    public function __invoke(string $slug, #[CurrentUser] ?Player $player): Response
    {
        $season = $this->seasonRepository->findBySlug($slug);

        if (!$season instanceof Season || !$season->isActive()) {
            throw new NotFoundHttpException('Saison non trouvée');
        }

        $individualRanking = $this->rankingService->calculateSeasonIndividualRanking($season);
        $teamRanking = $this->rankingService->calculateSeasonTeamRanking($season);

        return new Response(
            $this->twig->render('championship/season/show.html.twig', [
                'season' => $season,
                'individualRanking' => $individualRanking,
                'teamRanking' => $teamRanking,
                'player' => $player,
            ])
        );
    }
}
