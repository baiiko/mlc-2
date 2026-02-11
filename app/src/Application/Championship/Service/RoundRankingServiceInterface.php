<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\Round;

interface RoundRankingServiceInterface
{
    /**
     * Calculate qualification ranking for a round.
     *
     * @return array<array{position: int, login: string, pseudo: string, points: int, bonus: int, total: int, nbMaps: int, availableSemiFinal: bool, availableFinal: bool}>
     */
    public function calculateQualificationRanking(Round $round, Phase $phase): array;
}
