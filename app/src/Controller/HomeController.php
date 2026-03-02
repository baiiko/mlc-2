<?php

namespace App\Controller;

use App\Application\Championship\Service\CalculateRankingServiceInterface;
use App\Application\Championship\Service\ServerInfoService;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Championship\Repository\SeasonRepositoryInterface;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class HomeController
{
    public function __construct(
        private Environment $twig,
        private ServerRepositoryInterface $serverRepository,
        private ServerInfoService $serverInfoService,
        private RoundRepositoryInterface $roundRepository,
        private SeasonRepositoryInterface $seasonRepository,
        private CalculateRankingServiceInterface $calculateRankingService,
    ) {}

    #[Route('/', name: 'app_home')]
    public function __invoke(): Response
    {
        // Manche en cours ou à venir
        $currentRound = $this->roundRepository->findCurrentOrUpcoming();

        // Saison active
        $activeSeason = $this->seasonRepository->findActive();

        // Classement saison individuel (top 10)
        $seasonRanking = [];
        if ($activeSeason) {
            $seasonRanking = array_slice(
                $this->calculateRankingService->calculateSeasonIndividualRanking($activeSeason),
                0,
                10
            );
        }

        // Serveurs actifs avec infos en temps réel
        $servers = $this->serverRepository->findActive();
        $serversInfo = $this->serverInfoService->getMultipleServersInfo($servers);

        return new Response(
            $this->twig->render('home/index.html.twig', [
                'currentRound' => $currentRound,
                'activeSeason' => $activeSeason,
                'seasonRanking' => $seasonRanking,
                'serversInfo' => $serversInfo,
            ])
        );
    }
}
