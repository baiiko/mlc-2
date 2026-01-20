<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\IndividualRankingDTO;
use App\Application\Championship\DTO\SeasonRankingDTO;
use App\Application\Championship\DTO\TeamRankingDTO;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\Season;

interface CalculateRankingServiceInterface
{
    /**
     * @return IndividualRankingDTO[]
     */
    public function calculatePhaseIndividualRanking(Phase $phase): array;

    /**
     * @return TeamRankingDTO[]
     */
    public function calculatePhaseTeamRanking(Phase $phase, int $topPlayersCount = 4): array;

    /**
     * @return SeasonRankingDTO[]
     */
    public function calculateSeasonIndividualRanking(Season $season): array;

    /**
     * @return SeasonRankingDTO[]
     */
    public function calculateSeasonTeamRanking(Season $season): array;
}
