<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface TransferOwnershipServiceInterface
{
    /**
     * Transfer team ownership to another player.
     *
     * @throws \RuntimeException if current player is not the creator
     * @throws \InvalidArgumentException if new creator is not a team member
     */
    public function transferOwnership(Team $team, Player $currentCreator, int $newCreatorId): void;
}
