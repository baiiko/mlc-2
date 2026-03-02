<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\TeamJoinRequest;

interface JoinTeamServiceInterface
{
    /**
     * Request to join a team.
     *
     * @return bool true if request was created, false if player already has a pending request
     *
     * @throws \InvalidArgumentException if team doesn't exist
     */
    public function requestJoin(Player $player, int $teamId): bool;

    /**
     * Cancel a pending join request.
     */
    public function cancelRequest(Player $player): void;

    /**
     * Get pending request for a player.
     */
    public function getPendingRequest(Player $player): ?TeamJoinRequest;
}
