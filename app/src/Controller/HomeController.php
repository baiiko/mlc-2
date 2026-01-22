<?php

namespace App\Controller;

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
    ) {}

    #[Route('/', name: 'app_home')]
    public function __invoke(): Response
    {
        // Données de test pour le classement
        $players = [
            ['rank' => 1, 'name' => 'Speedy_King', 'team' => 'Team Velocity', 'initials' => 'SK', 'points' => 2847, 'wins' => 8, 'podiums' => 14, 'trend' => 'same', 'trend_value' => 0],
            ['rank' => 2, 'name' => 'NightRacer_FR', 'team' => 'French Drift', 'initials' => 'NR', 'points' => 2654, 'wins' => 6, 'podiums' => 12, 'trend' => 'up', 'trend_value' => 1],
            ['rank' => 3, 'name' => 'TurboX_', 'team' => 'X-Racing', 'initials' => 'TX', 'points' => 2521, 'wins' => 5, 'podiums' => 11, 'trend' => 'down', 'trend_value' => 1],
            ['rank' => 4, 'name' => 'DriftMaster', 'team' => 'Solo', 'initials' => 'DM', 'points' => 2398, 'wins' => 4, 'podiums' => 9, 'trend' => 'up', 'trend_value' => 2],
            ['rank' => 5, 'name' => 'ZenFlow', 'team' => 'Chill Racing', 'initials' => 'ZF', 'points' => 2245, 'wins' => 3, 'podiums' => 8, 'trend' => 'same', 'trend_value' => 0],
        ];

        // Manche en cours ou à venir
        $currentRound = $this->roundRepository->findCurrentOrUpcoming();

        // Saison active
        $activeSeason = $this->seasonRepository->findActive();

        // Serveurs actifs avec infos en temps réel
        $servers = $this->serverRepository->findActive();
        $serversInfo = $this->serverInfoService->getMultipleServersInfo($servers);

        return new Response(
            $this->twig->render('home/index.html.twig', [
                'players' => $players,
                'currentRound' => $currentRound,
                'activeSeason' => $activeSeason,
                'serversInfo' => $serversInfo,
            ])
        );
    }
}
