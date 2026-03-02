<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

final readonly class SeasonRankingDTO
{
    /**
     * @param array<int, int> $roundScores Map of round_id => points
     */
    public function __construct(
        public int $position,
        public Player|Team $entity,
        public int $totalPoints,
        public array $roundScores,
    ) {
    }
}
