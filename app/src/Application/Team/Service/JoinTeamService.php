<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamJoinRequest;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class JoinTeamService implements JoinTeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
    ) {
    }

    public function requestJoin(Player $player, int $teamId): bool
    {
        $team = $this->teamRepository->findById($teamId);

        if (!$team instanceof Team) {
            throw new \InvalidArgumentException('Équipe introuvable.');
        }

        // Check if player already has a pending request (only one allowed at a time)
        $existingRequest = $this->joinRequestRepository->findPendingByPlayer($player);

        if ($existingRequest instanceof TeamJoinRequest) {
            return false;
        }

        $request = new TeamJoinRequest($player, $team);
        $this->joinRequestRepository->save($request);

        return true;
    }

    public function cancelRequest(Player $player): void
    {
        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);

        if ($pendingRequest instanceof TeamJoinRequest) {
            $this->joinRequestRepository->delete($pendingRequest);
        }
    }

    public function getPendingRequest(Player $player): ?TeamJoinRequest
    {
        return $this->joinRequestRepository->findPendingByPlayer($player);
    }
}
