<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface CloseTeamServiceInterface
{
    /**
     * Close a team (all members leave, team is soft deleted).
     *
     * @throws \RuntimeException if player is not the team creator
     */
    public function closeTeam(Team $team, Player $player): void;
}
