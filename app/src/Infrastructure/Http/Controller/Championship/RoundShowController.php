<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Application\Championship\Service\CalculateRankingServiceInterface;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class RoundShowController
{
    public function __construct(
        private Environment $twig,
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private CalculateRankingServiceInterface $rankingService,
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    #[Route('/championship/round/{id}', name: 'app_championship_round_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, #[CurrentUser] ?Player $player): Response
    {
        $round = $this->roundRepository->findById($id);

        if ($round === null || !$round->getSeason()?->isActive()) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        $playerRegistration = null;
        if ($player !== null) {
            $playerRegistration = $this->registrationRepository->findByRoundAndPlayer($round, $player);
        }

        $registrations = $this->registrationRepository->findByRound($round);

        // Get rankings for each playable phase
        $phaseRankings = [];
        foreach ($round->getPhases() as $phase) {
            if ($phase->isPlayable()) {
                $phaseRankings[$phase->getId()] = [
                    'individual' => $this->rankingService->calculatePhaseIndividualRanking($phase),
                    'team' => $this->rankingService->calculatePhaseTeamRanking($phase),
                ];
            }
        }

        // Get records for each map (with player pseudo)
        $mapRecords = [];
        foreach ($round->getMaps() as $map) {
            if ($map->getUid()) {
                $mapRecords[$map->getUid()] = $this->mapRecordRepository->findByMapUidWithPlayer($map->getUid());
            }
        }

        return new Response(
            $this->twig->render('championship/round/show.html.twig', [
                'round' => $round,
                'season' => $round->getSeason(),
                'registrations' => $registrations,
                'playerRegistration' => $playerRegistration,
                'player' => $player,
                'phaseRankings' => $phaseRankings,
                'mapRecords' => $mapRecords,
            ])
        );
    }
}
