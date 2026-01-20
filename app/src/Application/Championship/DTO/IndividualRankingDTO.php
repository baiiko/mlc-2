<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

final readonly class IndividualRankingDTO
{
    public function __construct(
        public int $position,
        public Player $player,
        public ?Team $team,
        public int $time,
        public string $formattedTime,
        public bool $isQualified = false,
        public ?string $qualifiedTo = null,
    ) {
    }
}
