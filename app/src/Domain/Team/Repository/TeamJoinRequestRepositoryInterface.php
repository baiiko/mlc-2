<?php

declare(strict_types=1);

namespace App\Domain\Team\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamJoinRequest;

interface TeamJoinRequestRepositoryInterface
{
    public function save(TeamJoinRequest $request): void;

    public function delete(TeamJoinRequest $request): void;

    public function findById(int $id): ?TeamJoinRequest;

    public function findPendingByPlayer(Player $player): ?TeamJoinRequest;

    public function findPendingByPlayerAndTeam(Player $player, Team $team): ?TeamJoinRequest;

    /**
     * @return TeamJoinRequest[]
     */
    public function findPendingByTeam(Team $team): array;
}
