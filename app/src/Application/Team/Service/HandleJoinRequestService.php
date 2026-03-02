<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamJoinRequest;
use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;

final readonly class HandleJoinRequestService implements HandleJoinRequestServiceInterface
{
    public function __construct(
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
    ) {
    }

    public function acceptRequest(int $requestId, Player $teamCreator): void
    {
        $request = $this->joinRequestRepository->findById($requestId);

        if (!$request instanceof TeamJoinRequest) {
            throw new \RuntimeException('Demande introuvable.');
        }

        $this->validateCreatorAccess($request, $teamCreator);

        $requestPlayer = $request->getPlayer();

        // Check if player doesn't already have a team
        if ($requestPlayer->hasTeam()) {
            $this->joinRequestRepository->delete($request);

            return;
        }

        $team = $request->getTeam();
        $membership = new TeamMembership($requestPlayer, $team);
        $this->membershipRepository->save($membership);

        $request->accept();
        $this->joinRequestRepository->save($request);
    }

    public function rejectRequest(int $requestId, Player $teamCreator): void
    {
        $request = $this->joinRequestRepository->findById($requestId);

        if (!$request instanceof TeamJoinRequest) {
            throw new \RuntimeException('Demande introuvable.');
        }

        $this->validateCreatorAccess($request, $teamCreator);

        $request->reject();
        $this->joinRequestRepository->save($request);
    }

    public function findById(int $id): ?TeamJoinRequest
    {
        return $this->joinRequestRepository->findById($id);
    }

    private function validateCreatorAccess(TeamJoinRequest $request, Player $teamCreator): void
    {
        if (!$teamCreator->isTeamCreator()) {
            throw new \RuntimeException('Accès refusé.');
        }

        $creatorTeam = $teamCreator->getTeam();

        if (!$creatorTeam instanceof Team || $request->getTeam()->getId() !== $creatorTeam->getId()) {
            throw new \RuntimeException('Accès refusé.');
        }
    }
}
