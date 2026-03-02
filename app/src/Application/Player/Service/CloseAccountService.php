<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;

final readonly class CloseAccountService implements CloseAccountServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
    ) {
    }

    public function closeAccount(Player $player): void
    {
        if ($player->isTeamCreator()) {
            throw new \RuntimeException(
                'Vous devez transférer la propriété ou clôturer votre équipe avant de fermer votre compte.'
            );
        }

        // Cancel pending join requests
        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);
        if ($pendingRequest !== null) {
            $this->joinRequestRepository->delete($pendingRequest);
        }

        // Leave team if member
        $membership = $player->getActiveMembership();
        if ($membership !== null) {
            $membership->leave();
            $this->membershipRepository->save($membership);
        }

        // Delete account (soft delete via Gedmo)
        $this->playerRepository->delete($player);
    }
}
