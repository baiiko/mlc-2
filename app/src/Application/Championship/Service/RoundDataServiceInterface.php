<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Round;
use App\Domain\Player\Entity\Player;

interface RoundDataServiceInterface
{
    /**
     * Get round data for display.
     *
     * @return array{
     *     round: Round,
     *     registrations: array,
     *     playerRegistration: mixed,
     *     phaseRankings: array,
     *     mapRecords: array
     * }
     * @throws \RuntimeException if round not found or season not active
     */
    public function getRoundData(int $roundId, ?Player $player): array;
}
