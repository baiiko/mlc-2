<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Application\Team\DTO\UpdateTeamDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface UpdateTeamServiceInterface
{
    /**
     * Update team information.
     *
     * @return array{success: bool, error: ?string}
     * @throws \RuntimeException if player is not the team creator
     */
    public function updateTeam(Team $team, Player $player, UpdateTeamDTO $dto): array;
}
