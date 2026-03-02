<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Team\Entity\Team;

final readonly class TeamRankingDTO
{
    /**
     * @param IndividualRankingDTO[] $topPlayers
     */
    public function __construct(
        public int $position,
        public Team $team,
        public int $totalTime,
        public string $formattedTime,
        public int $playerCount,
        public array $topPlayers,
        public int $roundPoints = 0,
    ) {
    }
}
