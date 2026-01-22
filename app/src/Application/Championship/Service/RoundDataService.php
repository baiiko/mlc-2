<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;

final readonly class RoundDataService implements RoundDataServiceInterface
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private CalculateRankingServiceInterface $rankingService,
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    public function getRoundData(int $roundId, ?Player $player): array
    {
        $round = $this->roundRepository->findById($roundId);

        if ($round === null || !$round->getSeason()?->isActive()) {
            throw new \RuntimeException('Manche non trouvée');
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

        return [
            'round' => $round,
            'registrations' => $registrations,
            'playerRegistration' => $playerRegistration,
            'phaseRankings' => $phaseRankings,
            'mapRecords' => $mapRecords,
        ];
    }
}
