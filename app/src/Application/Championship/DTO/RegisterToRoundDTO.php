<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Championship\Entity\Round;
use App\Domain\Player\Entity\Player;

final readonly class RegisterToRoundDTO
{
    public function __construct(
        public Round $round,
        public Player $player,
        public bool $availableSemiFinal1 = true,
        public bool $availableSemiFinal2 = true,
        public bool $availableFinal = true,
    ) {
    }
}
